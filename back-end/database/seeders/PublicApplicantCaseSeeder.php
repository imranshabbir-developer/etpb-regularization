<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Property;
use App\Models\User;
use App\Services\ArrearsService;
use App\Services\AreaConversionService;
use App\Services\EligibilityService;
use App\Services\RentAssessmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Citizen-facing demo cases owned by the public applicant accounts.
 *
 * DemoDataSeeder fills officer dashboards. This seeder fills what an applicant
 * sees when they sign in — without it, demo.applicant and sohan.lal have
 * accounts but empty "My applications" screens on a fresh laptop.
 *
 * Safe to re-run: removes only rows tagged with the PUBLIC_DEMO marker.
 */
class PublicApplicantCaseSeeder extends Seeder
{
    private const MARKER = 'PUBLIC_DEMO';

    public function run(): void
    {
        $this->purgePrevious();

        $demo = User::where('email', 'demo.applicant@example.com')->first();
        $sohan = User::where('email', 'sohan.lal@example.com')->first();
        $do = User::where('email', 'do.lhr@etpb.gov.pk')->first();
        $admin = User::where('email', 'admin.lhr@etpb.gov.pk')->first();

        $lahore = DB::table('districts')->where('name', 'Lahore')->first();
        if (! $lahore) {
            $this->command?->error('  Lahore district missing — run GeographySeeder first.');

            return;
        }

        $profileId = app(AreaConversionService::class)->defaultProfileId();
        $created = 0;

        if ($demo) {
            $this->seedDemoApplicantRegularized($demo, $lahore, $profileId, $do, $admin);
            $created++;
        }

        if ($sohan) {
            $this->seedSohanDrafts($sohan, $lahore, $profileId);
            $created += 2;
        }

        $this->command?->newLine();
        $this->command?->info("  Seeded {$created} public-applicant cases (demo regularized + sohan drafts).");
        $this->command?->newLine();
    }

    private function purgePrevious(): void
    {
        $ids = DB::table('applications')
            ->where('status_remarks', self::MARKER)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        // Child tables first — same order as soft operational deletes would need.
        foreach ([
            'regularization_orders', 'tenancy_agreements', 'nominee_heirs', 'nominees',
            'approvals', 'payment_receipts', 'arrears_ledger', 'rent_schedules',
            'assessment_comparables', 'assessment_rate_inputs', 'assessment_decisions',
            'assessment_rounds', 'objections', 'public_notices', 'fee_payments',
            'application_documents', 'application_status_history', 'possession_details',
            'litigations',
        ] as $table) {
            if ($table === 'nominee_heirs') {
                $nomineeIds = DB::table('nominees')->whereIn('application_id', $ids)->pluck('id');
                DB::table('nominee_heirs')->whereIn('nominee_id', $nomineeIds)->delete();
                continue;
            }
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->whereIn('application_id', $ids)->delete();
            }
        }

        $propertyIds = DB::table('applications')->whereIn('id', $ids)->pluck('property_id');
        $applicantIds = DB::table('applications')->whereIn('id', $ids)->pluck('applicant_id');

        DB::table('applications')->whereIn('id', $ids)->delete();

        DB::table('property_geo_tags')->whereIn('property_id', $propertyIds)->delete();
        DB::table('property_areas')->whereIn('property_id', $propertyIds)->delete();
        DB::table('properties')->whereIn('id', $propertyIds)->delete();

        // Only drop applicant rows that this seeder created (still linked to the
        // public accounts and no longer referenced by any application).
        foreach ($applicantIds as $applicantId) {
            $stillUsed = DB::table('applications')->where('applicant_id', $applicantId)->exists();
            if (! $stillUsed) {
                // Keep the profile rows owned by ApplicantAccountSeeder; only
                // remove orphan property-linked clones if any remain unused.
            }
        }
    }

    private function seedDemoApplicantRegularized(
        User $user,
        object $lahore,
        int $profileId,
        ?User $do,
        ?User $admin,
    ): void {
        $area = app(AreaConversionService::class);
        $eligibility = app(EligibilityService::class);

        $applicant = Applicant::query()
            ->where('user_id', $user->id)
            ->where('cnic', $user->cnic)
            ->first();

        if (! $applicant) {
            $applicant = Applicant::create([
                'user_id'             => $user->id,
                'full_name'           => $user->name,
                'parentage_type'      => 'FATHER',
                'parentage_name'      => 'Demo Father',
                'cnic'                => $user->cnic,
                'contact'             => $user->contact,
                'email'               => $user->email,
                'postal_address'      => 'Model Town, Lahore',
                'address_district_id' => $lahore->id,
                'created_by'          => $user->id,
            ]);
        }

        $dims = [0, 10, 0];
        $converted = $area->toSqft(
            array_filter(['KANAL' => $dims[0], 'MARLA' => $dims[1], 'SARSAI' => $dims[2]]),
            $profileId,
        );
        $assessment = $eligibility->assess('2002-06-15');
        $rentAmount = 17500;

        $property = Property::create([
            'property_no'   => 'DEMO-MT-01',
            'property_type' => 'HOUSE',
            'usage_type'    => 'RESIDENTIAL',
            'address'       => 'Model Town, Lahore',
            'province_id'   => $lahore->province_id,
            'district_id'   => $lahore->id,
            'city'          => 'Lahore',
            'khasra_no'     => '501/1',
        ]);

        $property->areas()->create([
            'unit_profile_id'  => $profileId,
            'entry_mode'       => 'COMPOUND',
            'kanals'           => null,
            'marlas'           => 10,
            'sarsais'          => null,
            'area_sqft'        => $converted['sqft'],
            'conversion_trace' => $converted['trace'],
            'is_current'       => true,
        ]);

        $application = Application::create([
            'application_no'       => Application::nextApplicationNo((int) $lahore->id),
            'applicant_id'         => $applicant->id,
            'property_id'          => $property->id,
            'district_id'          => $lahore->id,
            'unit_profile_id'      => $profileId,
            'status'               => 'REGULARIZED',
            'payment_status'       => 'PAID',
            'payment_confirmed_at' => now()->subDays(40),
            'assigned_do_id'       => $do?->id,
            'assigned_admin_id'    => $admin?->id,
            'submitted_at'         => now()->subDays(60),
            'status_remarks'       => self::MARKER,
            'created_by'           => $user->id,
        ]);

        $application->possession()->create([
            'date_of_possession' => '2002-06-15',
            'possession_nature'  => 'SELF',
            'arrears_from'       => $assessment['arrears_from'],
            'arrears_from_basis' => $assessment['arrears_from_basis'],
            'is_eligible'        => $assessment['is_eligible'],
            'eligibility_reason' => $assessment['reason'],
            'cutoff_applied'     => $assessment['cutoff_applied'],
        ]);

        DB::table('fee_payments')->insert([
            'application_id'  => $application->id,
            'instrument_type' => 'PAY_ORDER',
            'instrument_no'   => 'PO-DEMO-5000',
            'instrument_date' => now()->subDays(58)->toDateString(),
            'amount'          => '5000.00',
            'payee'           => 'Chairman ETPB',
            'bank_name'       => 'National Bank of Pakistan',
            'branch_name'     => 'Lahore Main',
            'branch_code'     => '0100',
            'district_id'     => $lahore->id,
            'depositor_name'  => $user->name,
            'depositor_cnic'  => $user->cnic,
            'depositor_contact' => $user->contact,
            'submission_date' => now()->subDays(57)->toDateString(),
            'status'          => 'VERIFIED',
            'verified_at'     => now()->subDays(40),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('application_status_history')->insert([
            'application_id' => $application->id,
            'from_status'    => null,
            'to_status'      => 'REGULARIZED',
            'action'         => 'PUBLIC_DEMO_SEED',
            'remarks'        => 'Seeded for the demo.applicant account.',
            'actor_role'     => 'SYSTEM_ADMIN',
            'occurred_at'    => now()->subDays(60),
        ]);

        $roundId = DB::table('assessment_rounds')->insertGetId([
            'application_id'           => $application->id,
            'property_id'              => $property->id,
            'round_no'                 => 1,
            'round_type'               => 'INITIAL',
            'base_date'                => '2006-07-01',
            'effective_from'           => '2006-07-01',
            'enhancement_rate'         => '8.00',
            'enhancement_method'       => 'COMPOUND',
            'reassessment_cycle_years' => 6,
            'status'                   => 'DECIDED',
            'district_officer_id'      => $do?->id,
            'proposed_monthly_rent'    => $rentAmount + 2000,
            'determined_monthly_rent'  => $rentAmount,
            'first_notice_date'        => now()->subDays(35)->toDateString(),
            'completion_due_date'      => now()->subDays(35)->addDays(60)->toDateString(),
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        DB::table('assessment_decisions')->insert([
            'assessment_round_id'     => $roundId,
            'determined_monthly_rent' => (string) $rentAmount,
            'reasons'                 => 'Rent fixed for the public demo applicant case after notice.',
            'decided_by'              => $do?->id ?? 1,
            'decided_at'              => now()->subDays(20),
            'is_superseded'           => false,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        app(RentAssessmentService::class)->generateSchedule($roundId);
        app(ArrearsService::class)->generate($application->id);
        $application->refresh();

        $full = (float) $application->total_arrears;
        if ($full > 0) {
            app(ArrearsService::class)->postReceipt(
                $application->id,
                (string) $full,
                now()->subDays(12)->toDateString(),
                'BANK_TRANSFER',
            );
        }

        DB::table('approvals')->insert([
            'application_id'  => $application->id,
            'level'           => 'ADMINISTRATOR',
            'action'          => 'APPROVE',
            'reasons'         => 'Public demo case approved after arrears clearance.',
            'acted_by'        => $admin?->id ?? 3,
            'acted_at'        => now()->subDays(8),
            'due_by'          => now()->subDays(2)->toDateString(),
            'is_within_sla'   => true,
            'days_taken'      => 10,
            'order_reference' => 'ADMN/ROP/DEMO/001',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('tenancy_agreements')->insert([
            'application_id'  => $application->id,
            'agreement_no'    => $application->application_no . '/TA/01',
            'executed_on'     => now()->subDays(5)->toDateString(),
            'executed_by'     => $admin?->id ?? 4,
            'applicant_id'    => $applicant->id,
            'monthly_rent'    => (string) $rentAmount,
            'security_amount' => (string) ($rentAmount * 3),
            'effective_from'  => now()->subDays(5)->toDateString(),
            'stamp_paper_no'  => 'SP-DEMO-01',
            'status'          => 'EXECUTED',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('regularization_orders')->insert([
            'application_id'        => $application->id,
            'order_no'              => $application->application_no . '/ORD/01',
            'order_date'            => now()->subDays(4)->toDateString(),
            'issued_by'             => $admin?->id ?? 4,
            'issued_by_designation' => 'Deputy Administrator',
            'order_text'            => 'Possession of Demo Applicant over DEMO-MT-01 is regularized under Clause 3(ii).',
            'regularized_area_sqft' => $converted['sqft'],
            'monthly_rent_fixed'    => (string) $rentAmount,
            'status'                => 'ISSUED',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $application->forceFill([
            'assessed_monthly_rent' => (string) $rentAmount,
            'rent_fixed_at'         => now()->subDays(20),
            'approved_at'           => now()->subDays(8),
            'regularized_at'        => now()->subDays(4),
            'first_notice_date'     => now()->subDays(35)->toDateString(),
            'assessment_due_date'   => now()->subDays(35)->addDays(60)->toDateString(),
        ])->save();
    }

    private function seedSohanDrafts(User $user, object $lahore, int $profileId): void
    {
        $area = app(AreaConversionService::class);
        $eligibility = app(EligibilityService::class);

        $applicant = Applicant::query()
            ->where('user_id', $user->id)
            ->where('cnic', $user->cnic)
            ->first();

        if (! $applicant) {
            $applicant = Applicant::create([
                'user_id'             => $user->id,
                'full_name'           => $user->name,
                'parentage_type'      => 'FATHER',
                'parentage_name'      => 'Kishan Chand',
                'cnic'                => $user->cnic,
                'contact'             => $user->contact,
                'email'               => $user->email,
                'postal_address'      => 'House 22, Sant Nagar, Lahore',
                'address_district_id' => $lahore->id,
                'created_by'          => $user->id,
            ]);
        }

        $drafts = [
            ['SOHAN-SN-01', [0, 8, 0], '2004-03-12'],
            ['SOHAN-SN-02', [0, 5, 2], '2006-11-08'],
        ];

        foreach ($drafts as [$propertyNo, $dims, $possession]) {
            $converted = $area->toSqft(
                array_filter(['KANAL' => $dims[0], 'MARLA' => $dims[1], 'SARSAI' => $dims[2]]),
                $profileId,
            );
            $assessment = $eligibility->assess($possession);

            $property = Property::create([
                'property_no'   => $propertyNo,
                'property_type' => 'HOUSE',
                'usage_type'    => 'RESIDENTIAL',
                'address'       => $propertyNo . ', Sant Nagar, Lahore',
                'province_id'   => $lahore->province_id,
                'district_id'   => $lahore->id,
                'city'          => 'Lahore',
            ]);

            $property->areas()->create([
                'unit_profile_id'  => $profileId,
                'entry_mode'       => 'COMPOUND',
                'kanals'           => $dims[0] ?: null,
                'marlas'           => $dims[1] ?: null,
                'sarsais'          => $dims[2] ?: null,
                'area_sqft'        => $converted['sqft'],
                'conversion_trace' => $converted['trace'],
                'is_current'       => true,
            ]);

            $application = Application::create([
                'application_no'  => Application::nextApplicationNo((int) $lahore->id),
                'applicant_id'    => $applicant->id,
                'property_id'     => $property->id,
                'district_id'     => $lahore->id,
                'unit_profile_id' => $profileId,
                'status'          => 'DRAFT',
                'payment_status'  => 'PENDING',
                'status_remarks'  => self::MARKER,
                'created_by'      => $user->id,
            ]);

            $application->possession()->create([
                'date_of_possession' => $possession,
                'possession_nature'  => 'INHERITED',
                'arrears_from'       => $assessment['arrears_from'],
                'arrears_from_basis' => $assessment['arrears_from_basis'],
                'is_eligible'        => $assessment['is_eligible'],
                'eligibility_reason' => $assessment['reason'],
                'cutoff_applied'     => $assessment['cutoff_applied'],
            ]);

            DB::table('application_status_history')->insert([
                'application_id' => $application->id,
                'from_status'    => null,
                'to_status'      => 'DRAFT',
                'action'         => 'PUBLIC_DEMO_SEED',
                'remarks'        => 'Draft seeded for sohan.lal@example.com.',
                'actor_role'     => 'APPLICANT',
                'occurred_at'    => now()->subDays(3),
            ]);
        }
    }
}
