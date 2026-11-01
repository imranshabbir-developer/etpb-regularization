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
 * sees when they sign in — without it, demo.applicant / imran / sohan have
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
        $imran = User::where('email', 'imran.shabbir@example.com')->first();
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
            $created += $this->seedPortfolio($demo, $lahore, $profileId, $do, $admin, 'DEMO', [
                ['DEMO-MT-01', [0, 10, 0], '2002-06-15', 'REGULARIZED', 17500],
                ['DEMO-MT-02', [0, 6, 0],  '2004-03-11', 'SUBMITTED', null],
                ['DEMO-MT-03', [0, 8, 2],  '2001-09-22', 'SCRUTINY', null],
                ['DEMO-MT-04', [1, 0, 0],  '1999-12-05', 'ASSESSMENT_PROPOSED', 16200],
                ['DEMO-MT-05', [0, 5, 0],  '2007-07-18', 'DRAFT', null],
            ]);
        }

        if ($imran) {
            $created += $this->seedPortfolio($imran, $lahore, $profileId, $do, $admin, 'IMRAN', [
                ['IMRAN-GN-01', [0, 9, 0],  '2000-04-14', 'REGULARIZED', 19800],
                ['IMRAN-GN-02', [0, 7, 0],  '2003-08-09', 'FEE_VERIFICATION', null],
                ['IMRAN-GN-03', [0, 11, 0], '2005-01-27', 'SITE_INSPECTION', null],
                ['IMRAN-GN-04', [0, 6, 3],  '2006-11-03', 'ARREARS_COMPUTED', 14100],
                ['IMRAN-GN-05', [0, 4, 0],  '2008-02-16', 'DRAFT', null],
            ]);
        }

        if ($sohan) {
            $created += $this->seedPortfolio($sohan, $lahore, $profileId, $do, $admin, 'SOHAN', [
                ['SOHAN-SN-01', [0, 8, 0],  '2004-03-12', 'DRAFT', null],
                ['SOHAN-SN-02', [0, 5, 2],  '2006-11-08', 'DRAFT', null],
                ['SOHAN-SN-03', [0, 7, 0],  '2002-05-21', 'SUBMITTED', null],
                ['SOHAN-SN-04', [0, 10, 0], '1998-09-30', 'OBJECTION_WINDOW', 18400],
                ['SOHAN-SN-05', [1, 1, 0],  '2001-07-07', 'PENDING_ADMIN_APPROVAL', 22100],
            ]);
        }

        $this->command?->newLine();
        $this->command?->info("  Seeded {$created} public-applicant cases across demo / imran / sohan.");
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

        DB::table('applications')->whereIn('id', $ids)->delete();

        DB::table('property_geo_tags')->whereIn('property_id', $propertyIds)->delete();
        DB::table('property_areas')->whereIn('property_id', $propertyIds)->delete();
        DB::table('properties')->whereIn('id', $propertyIds)->delete();
    }

    /**
     * @param  list<array{0:string,1:array{0:int,1:int,2:int},2:string,3:string,4:?int}>  $rows
     */
    private function seedPortfolio(
        User $user,
        object $lahore,
        int $profileId,
        ?User $do,
        ?User $admin,
        string $tag,
        array $rows,
    ): int {
        $applicant = $this->ensureApplicant($user, $lahore);
        $count = 0;

        foreach ($rows as $i => [$propertyNo, $dims, $possession, $stage, $rentAmount]) {
            $this->seedOne(
                $user, $applicant, $lahore, $profileId, $do, $admin,
                $propertyNo, $dims, $possession, $stage, $rentAmount, $i, $tag,
            );
            $count++;
        }

        return $count;
    }

    private function ensureApplicant(User $user, object $lahore): Applicant
    {
        $applicant = Applicant::query()
            ->where('user_id', $user->id)
            ->where('cnic', $user->cnic)
            ->first();

        if ($applicant) {
            return $applicant;
        }

        return Applicant::create([
            'user_id'             => $user->id,
            'full_name'           => $user->name,
            'parentage_type'      => 'FATHER',
            'parentage_name'      => 'Father of ' . $user->name,
            'cnic'                => $user->cnic,
            'contact'             => $user->contact,
            'email'               => $user->email,
            'postal_address'      => 'Lahore',
            'address_district_id' => $lahore->id,
            'created_by'          => $user->id,
        ]);
    }

    private function seedOne(
        User $user,
        Applicant $applicant,
        object $lahore,
        int $profileId,
        ?User $do,
        ?User $admin,
        string $propertyNo,
        array $dims,
        string $possession,
        string $stage,
        ?int $rentAmount,
        int $i,
        string $tag,
    ): void {
        $area = app(AreaConversionService::class);
        $eligibility = app(EligibilityService::class);

        $marlas = $dims[1] + (int) ceil(($dims[2] ?? 0) / 9);
        $converted = $area->toSqft(
            array_filter(['KANAL' => $dims[0], 'MARLA' => $marlas]),
            $profileId,
        );
        $assessment = $eligibility->assess($possession);
        $paid = ! in_array($stage, ['DRAFT', 'SUBMITTED'], true);

        $property = Property::create([
            'property_no'   => $propertyNo,
            'property_type' => 'RESIDENTIAL',
            'usage_type'    => 'RESIDENTIAL',
            'address'       => $propertyNo . ', Lahore',
            'province_id'   => $lahore->province_id,
            'district_id'   => $lahore->id,
            'city'          => 'Lahore',
            'khasra_no'     => (string) (700 + $i) . '/' . (1 + $i % 5),
        ]);

        $property->areas()->create([
            'unit_profile_id'  => $profileId,
            'entry_mode'       => 'COMPOUND',
            'kanals'           => $dims[0] ?: null,
            'marlas'           => $marlas ?: null,
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
            'status'               => $stage,
            'payment_status'       => $paid ? 'PAID' : 'PENDING',
            'payment_confirmed_at' => $paid ? now()->subDays(40 - $i) : null,
            'assigned_do_id'       => $do?->id,
            'assigned_admin_id'    => $admin?->id,
            'submitted_at'         => $stage === 'DRAFT' ? null : now()->subDays(55 - $i * 3),
            'status_remarks'       => self::MARKER,
            'created_by'           => $user->id,
        ]);

        $application->possession()->create([
            'date_of_possession' => $possession,
            'possession_nature'  => ['SELF', 'INHERITED', 'PURCHASED'][$i % 3],
            'arrears_from'       => $assessment['arrears_from'],
            'arrears_from_basis' => $assessment['arrears_from_basis'],
            'is_eligible'        => $assessment['is_eligible'],
            'eligibility_reason' => $assessment['reason'],
            'cutoff_applied'     => $assessment['cutoff_applied'],
        ]);

        if ($stage !== 'DRAFT') {
            DB::table('fee_payments')->insert([
                'application_id'    => $application->id,
                'instrument_type'   => 'PAY_ORDER',
                'instrument_no'     => 'PO-' . $tag . '-' . (5000 + $i),
                'instrument_date'   => now()->subDays(50 - $i)->toDateString(),
                'amount'            => '5000.00',
                'payee'             => 'Chairman ETPB',
                'bank_name'         => 'National Bank of Pakistan',
                'branch_name'       => 'Lahore Main',
                'branch_code'       => '0100',
                'district_id'       => $lahore->id,
                'depositor_name'    => $user->name,
                'depositor_cnic'    => $user->cnic,
                'depositor_contact' => $user->contact,
                'submission_date'   => now()->subDays(49 - $i)->toDateString(),
                'status'            => $paid ? 'VERIFIED' : 'PENDING',
                'verified_at'       => $paid ? now()->subDays(40 - $i) : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        DB::table('application_status_history')->insert([
            'application_id' => $application->id,
            'from_status'    => null,
            'to_status'      => $stage,
            'action'         => 'PUBLIC_DEMO_SEED',
            'remarks'        => "Seeded {$stage} case for {$user->email}.",
            'actor_role'     => 'SYSTEM_ADMIN',
            'occurred_at'    => now()->subDays(55 - $i * 3),
        ]);

        $needsAssessment = in_array($stage, [
            'ASSESSMENT_PROPOSED', 'NOTICE_ISSUED', 'OBJECTION_WINDOW', 'HEARING',
            'RENT_FIXED', 'ARREARS_COMPUTED', 'PENDING_ADMIN_APPROVAL', 'REGULARIZED',
        ], true);

        if ($needsAssessment && $rentAmount !== null) {
            $this->attachAssessmentAndClose($application, $stage, $rentAmount, $do, $admin, $i, $converted['sqft']);
        }
    }

    private function attachAssessmentAndClose(
        Application $application,
        string $stage,
        int $rentAmount,
        ?User $do,
        ?User $admin,
        int $i,
        mixed $areaSqft,
    ): void {
        $roundId = DB::table('assessment_rounds')->insertGetId([
            'application_id'           => $application->id,
            'property_id'              => $application->property_id,
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
            'first_notice_date'        => now()->subDays(35 - $i)->toDateString(),
            'completion_due_date'      => now()->subDays(35 - $i)->addDays(60)->toDateString(),
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        DB::table('assessment_decisions')->insert([
            'assessment_round_id'     => $roundId,
            'determined_monthly_rent' => (string) $rentAmount,
            'reasons'                 => 'Rent fixed for the public demo applicant portfolio case after notice.',
            'decided_by'              => $do?->id ?? 1,
            'decided_at'              => now()->subDays(20 - $i),
            'is_superseded'           => false,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        $noticeId = DB::table('public_notices')->insertGetId([
            'application_id'      => $application->id,
            'assessment_round_id' => $roundId,
            'notice_no'           => $application->application_no . '/NOT/01',
            'notice_type'         => 'PUBLIC',
            'issued_on'           => now()->subDays(35 - $i)->toDateString(),
            'served_on'           => now()->subDays(33 - $i)->toDateString(),
            'service_mode'        => 'NOTICE_BOARD',
            'objection_deadline'  => now()->subDays(33 - $i)->addDays(15)->toDateString(),
            'status'              => 'SERVED',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        if ($stage === 'OBJECTION_WINDOW') {
            DB::table('objections')->insert([
                'application_id'           => $application->id,
                'public_notice_id'         => $noticeId,
                'objection_no'             => $application->application_no . '/OBJ/01',
                'objector_name'            => 'Neighbour objector',
                'objector_cnic'            => '35201990000' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'relationship_to_property' => 'Adjoining occupant',
                'plea'                     => 'Proposed rent is excessive for the locality.',
                'filed_on'                 => now()->subDays(10)->toDateString(),
                'is_within_time'           => true,
                'status'                   => 'FILED',
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        app(RentAssessmentService::class)->generateSchedule($roundId);
        app(ArrearsService::class)->generate($application->id);
        $application->refresh();

        $application->forceFill([
            'assessed_monthly_rent' => (string) $rentAmount,
            'rent_fixed_at'         => in_array($stage, ['ASSESSMENT_PROPOSED', 'OBJECTION_WINDOW'], true)
                ? null : now()->subDays(20 - $i),
            'first_notice_date'     => now()->subDays(35 - $i)->toDateString(),
            'assessment_due_date'   => now()->subDays(35 - $i)->addDays(60)->toDateString(),
        ])->save();

        if ($stage === 'PENDING_ADMIN_APPROVAL') {
            $application->forceFill([
                'admin_approval_due_date' => now()->addDays(12)->toDateString(),
            ])->save();
            $part = round((float) $application->total_arrears * 0.35, 2);
            if ($part > 0) {
                app(ArrearsService::class)->postReceipt(
                    $application->id, (string) $part,
                    now()->subDays(8)->toDateString(), 'PAY_ORDER',
                );
            }

            return;
        }

        if ($stage === 'ARREARS_COMPUTED') {
            $part = round((float) $application->total_arrears * 0.15, 2);
            if ($part > 0) {
                app(ArrearsService::class)->postReceipt(
                    $application->id, (string) $part,
                    now()->subDays(5)->toDateString(), 'CASH',
                );
            }

            return;
        }

        if ($stage !== 'REGULARIZED') {
            return;
        }

        $full = (float) $application->total_arrears;
        if ($full > 0) {
            app(ArrearsService::class)->postReceipt(
                $application->id, (string) $full,
                now()->subDays(12)->toDateString(), 'BANK_TRANSFER',
            );
        }

        DB::table('approvals')->insert([
            'application_id'  => $application->id,
            'level'           => 'ADMINISTRATOR',
            'action'          => 'APPROVE',
            'reasons'         => 'Public demo portfolio case approved after arrears clearance.',
            'acted_by'        => $admin?->id ?? 3,
            'acted_at'        => now()->subDays(8),
            'due_by'          => now()->subDays(2)->toDateString(),
            'is_within_sla'   => true,
            'days_taken'      => 10,
            'order_reference' => 'ADMN/ROP/PUB/' . str_pad((string) (10 + $i), 3, '0', STR_PAD_LEFT),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('tenancy_agreements')->insert([
            'application_id'  => $application->id,
            'agreement_no'    => $application->application_no . '/TA/01',
            'executed_on'     => now()->subDays(5)->toDateString(),
            'executed_by'     => $do?->id ?? $admin?->id ?? 4,
            'applicant_id'    => $application->applicant_id,
            'monthly_rent'    => (string) $rentAmount,
            'security_amount' => (string) ($rentAmount * 3),
            'effective_from'  => now()->subDays(5)->toDateString(),
            'stamp_paper_no'  => 'SP-PUB-' . (100 + $i),
            'status'          => 'EXECUTED',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('regularization_orders')->insert([
            'application_id'        => $application->id,
            'order_no'              => $application->application_no . '/ORD/01',
            'order_date'            => now()->subDays(4)->toDateString(),
            'issued_by'             => $do?->id ?? $admin?->id ?? 4,
            'issued_by_designation' => 'Deputy Administrator',
            'order_text'            => 'Possession over ' . $application->property->property_no
                . ' is regularized under Clause 3(ii).',
            'regularized_area_sqft' => $areaSqft,
            'monthly_rent_fixed'    => (string) $rentAmount,
            'status'                => 'ISSUED',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $application->forceFill([
            'approved_at'    => now()->subDays(8),
            'regularized_at' => now()->subDays(4),
        ])->save();
    }
}
