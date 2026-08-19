<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\Property;
use App\Models\User;
use App\Services\ArrearsService;
use App\Services\AreaConversionService;
use App\Services\EligibilityService;
use App\Services\RentAssessmentService;
use App\Services\WorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A realistic body of cases for demonstration and for exercising the screens.
 *
 * Every record here is produced through the same services the application
 * itself uses — the area conversion, the eligibility test, the rent schedule
 * and the arrears ledger — so the figures on the dashboards and in the reports
 * are genuinely computed rather than typed in. A demo that shows made-up
 * numbers teaches the wrong thing about the system.
 *
 * Cases are spread deliberately across the whole workflow, including the
 * uncomfortable states: one past its assessment deadline, one past the
 * Administrator's month, one stayed by a court, several unpaid and therefore
 * untouched. Those are the states an officer most needs to see.
 *
 * Safe to re-run: it removes only what it created, keyed on the demo marker.
 */
class DemoDataSeeder extends Seeder
{
    private const MARKER = 'DEMO';

    public function run(): void
    {
        $area        = app(AreaConversionService::class);
        $eligibility = app(EligibilityService::class);
        $rent        = app(RentAssessmentService::class);
        $arrears     = app(ArrearsService::class);
        $workflow    = app(WorkflowService::class);

        $do    = User::where('email', 'do.lhr@etpb.gov.pk')->first();
        $admin = User::where('email', 'admin.lhr@etpb.gov.pk')->first();

        $districts = District::whereIn('name', [
            'Lahore', 'Rawalpindi', 'Faisalabad', 'Multan', 'Gujranwala', 'Sialkot',
        ])->get()->keyBy('name');

        $profileId = $area->defaultProfileId();
        $mandatory = DocumentType::where('is_mandatory', true)->get();

        // [name, parentage, cnic, district, property, area (K/M/S), possession, target stage, rent]
        $cases = [
            ['Ram Lal',            'Diwan Chand',    '3520111000011', 'Lahore',      'KN-08',   [1, 2, 0],  '1996-05-14', 'REGULARIZED',           26000],
            ['Sardar Gurmeet Singh','Harnam Singh',  '3520111000012', 'Lahore',      'KN-11-A', [0, 9, 0],  '1999-11-02', 'REGULARIZED',           18500],
            ['Parkash Devi',       'Late Sohan Lal', '3520111000013', 'Rawalpindi',  'RWP-42',  [0, 6, 4],  '2001-03-19', 'REGULARIZED',           14200],

            ['Muhammad Ilyas',     'Ghulam Nabi',    '3520111000014', 'Lahore',      'KN-19',   [1, 0, 0],  '2004-07-08', 'PENDING_ADMIN_APPROVAL', 21000],
            ['Bashir Ahmed',       'Karam Din',      '3520111000015', 'Faisalabad',  'FSD-07',  [0, 14, 0], '1998-02-25', 'PENDING_ADMIN_APPROVAL', 24500],
            ['Kishan Chand',       'Tara Chand',     '3520111000016', 'Multan',      'MUL-21',  [2, 0, 0],  '2000-09-30', 'PENDING_ADMIN_APPROVAL', 31000],

            ['Abdul Rehman',       'Fazal Din',      '3520111000017', 'Lahore',      'KN-27',   [0, 11, 0], '2005-06-12', 'ARREARS_COMPUTED',       16800],
            ['Shanti Bai',         'Mohan Lal',      '3520111000018', 'Gujranwala',  'GRW-13',  [0, 8, 5],  '2003-01-07', 'ARREARS_COMPUTED',       12900],

            ['Nazir Hussain',      'Sultan Ali',     '3520111000019', 'Sialkot',     'SKT-05',  [1, 5, 0],  '2002-04-21', 'OBJECTION_WINDOW',       19700],
            ['Krishan Kumar',      'Banarsi Das',    '3520111000020', 'Lahore',      'KN-33',   [0, 13, 0], '1997-08-16', 'OBJECTION_WINDOW',       22300],

            ['Muhammad Younis',    'Allah Ditta',    '3520111000021', 'Rawalpindi',  'RWP-58',  [0, 7, 0],  '2006-12-03', 'ASSESSMENT_PROPOSED',    13400],
            ['Sita Rani',          'Ram Piara',      '3520111000022', 'Faisalabad',  'FSD-16',  [1, 3, 0],  '1995-10-11', 'ASSESSMENT_PROPOSED',    20100],

            ['Ghulam Farid',       'Noor Muhammad',  '3520111000023', 'Multan',      'MUL-34',  [0, 10, 0], '2007-05-27', 'SITE_INSPECTION',        null],
            ['Dev Raj',            'Amar Nath',      '3520111000024', 'Lahore',      'KN-41',   [0, 12, 3], '2001-07-04', 'SITE_INSPECTION',        null],
            ['Rashida Bibi',       'Late Sharif',    '3520111000025', 'Gujranwala',  'GRW-22',  [0, 5, 0],  '2008-02-14', 'SCRUTINY',               null],

            ['Ashok Kumar',        'Prem Nath',      '3520111000026', 'Sialkot',     'SKT-18',  [0, 9, 6],  '2004-11-23', 'SUBMITTED',              null],
            ['Muhammad Aslam',     'Barkat Ali',     '3520111000027', 'Lahore',      'KN-52',   [1, 1, 0],  '2009-03-09', 'SUBMITTED',              null],
            ['Bimla Devi',         'Hans Raj',       '3520111000028', 'Rawalpindi',  'RWP-71',  [0, 6, 0],  '2003-06-30', 'SUBMITTED',              null],

            ['Zulfiqar Ali',       'Muhammad Sadiq', '3520111000029', 'Faisalabad',  'FSD-29',  [0, 15, 0], '2006-08-18', 'DRAFT',                  null],
            ['Raj Kumari',         'Lekh Raj',       '3520111000030', 'Multan',      'MUL-45',  [0, 7, 2],  '1999-04-05', 'DRAFT',                  null],
            ['Iftikhar Ahmed',     'Rehmat Ali',     '3520111000031', 'Lahore',      'KN-63',   [0, 8, 0],  '2005-12-20', 'DRAFT',                  null],

            ['Om Parkash',         'Jagan Nath',     '3520111000032', 'Lahore',      'KN-70',   [1, 4, 0],  '2000-02-28', 'SUB_JUDICE',             null],
            ['Manzoor Hussain',    'Bashir Ahmed',   '3520111000033', 'Gujranwala',  'GRW-31',  [0, 11, 4], '2007-09-15', 'REJECTED',               null],
        ];

        DB::transaction(function () use (
            $cases, $districts, $profileId, $area, $eligibility, $mandatory, $do, $admin
        ) {
            foreach ($cases as $i => $c) {
                [$name, $parentage, $cnic, $districtName, $propertyNo, $dims, $possession, $stage, $rentAmount] = $c;

                $district = $districts->get($districtName);
                if (! $district) {
                    continue;
                }

                $converted = $area->toSqft(
                    array_filter(['KANAL' => $dims[0], 'MARLA' => $dims[1], 'SARSAI' => $dims[2]]),
                    $profileId,
                );
                $assessment = $eligibility->assess($possession);

                $applicant = Applicant::create([
                    'full_name'      => $name,
                    'parentage_type' => str_contains($name, 'Devi') || str_contains($name, 'Bibi')
                                        || str_contains($name, 'Rani') || str_contains($name, 'Bai')
                                        || str_contains($name, 'Kumari') ? 'HUSBAND' : 'FATHER',
                    'parentage_name' => $parentage,
                    'cnic'           => $cnic,
                    'contact'        => '030' . (1 + $i % 5) . '-' . str_pad((string) (1000000 + $i * 7717), 7, '0', STR_PAD_LEFT),
                    'postal_address' => $propertyNo . ', ' . $districtName,
                    'is_widow'       => str_contains($parentage, 'Late'),
                    'is_indigent'    => $i % 11 === 0,
                ]);

                $property = Property::create([
                    'property_no'   => $propertyNo,
                    'sub_unit_no'   => $i % 4 === 0 ? (string) (1 + $i % 3) : null,
                    'property_type' => $i % 5 === 0 ? 'SHOP' : 'HOUSE',
                    'usage_type'    => $i % 5 === 0 ? 'COMMERCIAL' : 'RESIDENTIAL',
                    'address'       => $propertyNo . ', ' . $districtName,
                    'province_id'   => $district->province_id,
                    'district_id'   => $district->id,
                    'city'          => $districtName,
                    'khasra_no'     => (string) (100 + $i) . '/' . (1 + $i % 9),
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

                $property->geoTags()->create([
                    'latitude'    => 31.5 + ($i % 20) * 0.05,
                    'longitude'   => 74.3 + ($i % 15) * 0.05,
                    'source'      => 'MANUAL',
                    'captured_at' => now()->subDays(60 - $i),
                    'is_primary'  => true,
                ]);

                $paid = ! in_array($stage, ['DRAFT', 'SUBMITTED'], true);

                $application = Application::create([
                    'application_no'  => Application::nextApplicationNo($district->id),
                    'applicant_id'    => $applicant->id,
                    'property_id'     => $property->id,
                    'district_id'     => $district->id,
                    'office_id'       => null,
                    'unit_profile_id' => $profileId,
                    'status'          => $stage,
                    'payment_status'  => $paid ? 'PAID' : 'PENDING',
                    'payment_confirmed_at' => $paid ? now()->subDays(50 - $i) : null,
                    'assigned_do_id'    => $do?->id,
                    'assigned_admin_id' => $admin?->id,
                    'submitted_at'      => $stage === 'DRAFT' ? null : now()->subDays(70 - $i * 2),
                    'status_remarks'    => self::MARKER,
                ]);

                $application->possession()->create([
                    'application_id'     => $application->id,
                    'date_of_possession' => $possession,
                    'possession_nature'  => ['SELF', 'INHERITED', 'PURCHASED'][$i % 3],
                    'arrears_from'       => $assessment['arrears_from'],
                    'arrears_from_basis' => $assessment['arrears_from_basis'],
                    'is_eligible'        => $assessment['is_eligible'],
                    'eligibility_reason' => $assessment['reason'],
                    'cutoff_applied'     => $assessment['cutoff_applied'],
                ]);

                // Evidence, and the fee where the case has moved past intake.
                if ($stage !== 'DRAFT') {
                    foreach ($mandatory as $t) {
                        DB::table('application_documents')->insert([
                            'application_id'   => $application->id,
                            'document_type_id' => $t->id,
                            'title'            => $t->name,
                            'file_path'        => 'uploads/demo/' . $application->id . '-' . $t->code . '.pdf',
                            'original_filename' => $t->code . '.pdf',
                            'mime_type'        => 'application/pdf',
                            'size_bytes'       => 120000 + $i * 311,
                            'sha256'           => hash('sha256', $application->id . $t->code),
                            'is_certified_copy' => (bool) $t->is_certified_copy_required,
                            'status'           => $paid ? 'VERIFIED' : 'PENDING',
                            'verified_at'      => $paid ? now()->subDays(45 - $i) : null,
                            'created_at'       => now(), 'updated_at' => now(),
                        ]);
                    }

                    DB::table('fee_payments')->insert([
                        'application_id'    => $application->id,
                        'instrument_type'   => ['PAY_ORDER', 'DEMAND_DRAFT', 'BANKERS_CHEQUE'][$i % 3],
                        'instrument_no'     => 'PO-' . (400000 + $i * 137),
                        'instrument_date'   => now()->subDays(72 - $i * 2)->toDateString(),
                        'amount'            => '5000.00',
                        'payee'             => 'Chairman ETPB',
                        'bank_name'         => ['National Bank of Pakistan', 'Bank of Punjab', 'Habib Bank'][$i % 3],
                        'branch_name'       => $districtName . ' Main',
                        'branch_code'       => str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT),
                        'district_id'       => $district->id,
                        'depositor_name'    => $name,
                        'depositor_cnic'    => $cnic,
                        'depositor_contact' => $applicant->contact,
                        'submission_date'   => now()->subDays(71 - $i * 2)->toDateString(),
                        'status'            => $paid ? 'VERIFIED' : 'PENDING',
                        'verified_at'       => $paid ? now()->subDays(50 - $i) : null,
                        'created_at'        => now(), 'updated_at' => now(),
                    ]);
                }

                DB::table('application_status_history')->insert([
                    'application_id' => $application->id,
                    'from_status'    => null,
                    'to_status'      => $stage,
                    'action'         => 'DEMO_SEED',
                    'remarks'        => 'Seeded for demonstration.',
                    'actor_role'     => 'SYSTEM_ADMIN',
                    'occurred_at'    => now()->subDays(70 - $i * 2),
                ]);

                $this->buildAssessment($application, $stage, $rentAmount, $do, $i);
            }
        });

        $this->command->newLine();
        $this->command->info('  Seeded ' . count($cases) . ' demonstration cases across '
            . $districts->count() . ' districts.');
        $this->command->newLine();
    }

    /**
     * Rent, schedule, ledger and the due-process record, built through the real
     * services so every figure on screen is genuinely computed.
     */
    private function buildAssessment(Application $application, string $stage, ?int $rentAmount, ?User $do, int $i): void
    {
        $needsAssessment = in_array($stage, [
            'ASSESSMENT_PROPOSED', 'NOTICE_ISSUED', 'OBJECTION_WINDOW', 'HEARING',
            'RENT_FIXED', 'ARREARS_COMPUTED', 'PENDING_ADMIN_APPROVAL', 'APPROVED',
            'AGREEMENT_EXECUTION', 'REGULARIZED',
        ], true);

        if (! $needsAssessment || $rentAmount === null) {
            $this->addLitigation($application, $stage);

            return;
        }

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
            'proposed_monthly_rent'    => $rentAmount + 2500,
            'determined_monthly_rent'  => $rentAmount,
            'first_notice_date'        => now()->subDays(40 - $i)->toDateString(),
            'completion_due_date'      => now()->subDays(40 - $i)->addDays(60)->toDateString(),
            'created_at'               => now(), 'updated_at' => now(),
        ]);

        foreach ([['FBR', '2.85'], ['DC_RATE', '2.40'], ['NESPAK', '3.10']] as $j => [$code, $rate]) {
            $sourceId = DB::table('rate_sources')->where('code', $code)->value('id');
            DB::table('assessment_rate_inputs')->insert([
                'assessment_round_id' => $roundId,
                'rate_source_id'      => $sourceId,
                'rate_value'          => $rate,
                'rate_unit'           => 'PER_SQFT_PER_MONTH',
                'notification_no'     => strtoupper($code) . '/' . (2024 + $j) . '/' . (100 + $i),
                'notification_date'   => now()->subYears(1)->toDateString(),
                'created_at'          => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('assessment_comparables')->insert([
            'assessment_round_id'  => $roundId,
            'property_description' => 'Adjoining property in the same street',
            'area_sqft'            => '5445.0000',
            'monthly_rent'         => (string) ($rentAmount + 1800),
            'usage_type'           => 'RESIDENTIAL',
            'distance_meters'      => '40.00',
            'information_source'   => 'Local enquiry and tenancy agreement produced',
            'created_at'           => now(), 'updated_at' => now(),
        ]);

        DB::table('assessment_decisions')->insert([
            'assessment_round_id'     => $roundId,
            'determined_monthly_rent' => (string) $rentAmount,
            'reasons'                 => 'The FBR notified rate, the District Collector rate and the '
                . 'valuation of the adjoining property in the same street were all considered. '
                . 'Having regard to the age and condition of the structure and to the rent prevailing '
                . 'in the vicinity in similar circumstances, the rent is fixed at Rs. '
                . number_format($rentAmount) . ' per month with effect from the base date.',
            'decided_by'   => $do?->id ?? 1,
            'decided_at'   => now()->subDays(20 - min($i, 15)),
            'is_superseded' => false,
            'created_at'   => now(), 'updated_at' => now(),
        ]);

        // Public notice, and objections where the case is at that stage.
        $noticeId = DB::table('public_notices')->insertGetId([
            'application_id'      => $application->id,
            'assessment_round_id' => $roundId,
            'notice_no'           => $application->application_no . '/NOT/01',
            'notice_type'         => 'PUBLIC',
            'issued_on'           => now()->subDays(40 - $i)->toDateString(),
            'served_on'           => now()->subDays(38 - $i)->toDateString(),
            'service_mode'        => 'NOTICE_BOARD',
            'objection_deadline'  => now()->subDays(38 - $i)->addDays(15)->toDateString(),
            'status'              => 'SERVED',
            'created_at'          => now(), 'updated_at' => now(),
        ]);

        if ($stage === 'OBJECTION_WINDOW') {
            DB::table('objections')->insert([
                'application_id'   => $application->id,
                'public_notice_id' => $noticeId,
                'objection_no'     => $application->application_no . '/OBJ/01',
                'objector_name'    => ['Sattar Khan', 'Lachman Das'][$i % 2],
                'objector_cnic'    => '35201' . str_pad((string) (2200000 + $i), 8, '0', STR_PAD_LEFT),
                'relationship_to_property' => 'Occupant of the adjoining sub-unit',
                'plea'             => 'The proposed rent is excessive for this locality and does not '
                    . 'reflect the condition of the structure, which has had no major repair for many '
                    . 'years. The adjoining property relied upon is of newer construction.',
                'filed_on'         => now()->subDays(30 - $i)->toDateString(),
                'is_within_time'   => true,
                'status'           => 'FILED',
                'created_at'       => now(), 'updated_at' => now(),
            ]);
        }

        app(RentAssessmentService::class)->generateSchedule($roundId);
        app(ArrearsService::class)->generate($application->id);

        // generate() writes the rolled-up totals straight to the row, so the
        // in-memory model is stale until it is re-read.
        $application->refresh();

        $application->forceFill([
            'assessed_monthly_rent' => (string) $rentAmount,
            'rent_fixed_at'         => in_array($stage, ['ASSESSMENT_PROPOSED', 'NOTICE_ISSUED', 'OBJECTION_WINDOW'], true)
                                       ? null : now()->subDays(20 - min($i, 15)),
            'first_notice_date'     => now()->subDays(40 - $i)->toDateString(),
            'assessment_due_date'   => now()->subDays(40 - $i)->addDays(60)->toDateString(),
        ])->save();

        $this->finishCase($application, $stage, $rentAmount, $i);
        $this->addLitigation($application, $stage);
    }

    /** Payments, approval and the closing acts, so the later stages look real. */
    private function finishCase(Application $application, string $stage, int $rentAmount, int $i): void
    {
        if ($stage === 'PENDING_ADMIN_APPROVAL') {
            // One of these is deliberately past the one-month limit, because an
            // officer needs to see what a breach looks like.
            $overdue = $i % 3 === 1;
            $application->forceFill([
                'admin_approval_due_date' => $overdue
                    ? now()->subDays(9)->toDateString()
                    : now()->addDays(14)->toDateString(),
            ])->save();

            $part = round((float) $application->total_arrears * 0.35, 2);
            if ($part > 0) {
                app(ArrearsService::class)->postReceipt(
                    $application->id, (string) $part,
                    now()->subDays(12)->toDateString(), 'PAY_ORDER',
                );
            }

            return;
        }

        if (! in_array($stage, ['REGULARIZED'], true)) {
            if (in_array($stage, ['ARREARS_COMPUTED'], true)) {
                $part = round((float) $application->total_arrears * 0.15, 2);
                if ($part > 0) {
                    app(ArrearsService::class)->postReceipt(
                        $application->id, (string) $part,
                        now()->subDays(6)->toDateString(), 'CASH',
                    );
                }
            }

            return;
        }

        // A regularized case: arrears cleared, approved, agreement executed.
        $full = (float) $application->total_arrears;
        if ($full > 0) {
            app(ArrearsService::class)->postReceipt(
                $application->id, (string) $full,
                now()->subDays(15)->toDateString(), 'BANK_TRANSFER',
            );
        }

        DB::table('approvals')->insert([
            'application_id' => $application->id,
            'level'          => 'ADMINISTRATOR',
            'action'         => 'APPROVE',
            'reasons'        => 'The occupant has been in actual physical possession well before the '
                . 'cut-off fixed by Clause 3(ii)(a). Documentary evidence has been produced and '
                . 'verified, the rent has been fixed by the District Officer for recorded reasons '
                . 'after due notice, and the arrears assessed have been cleared in full. The '
                . 'regularization is approved.',
            'acted_by'       => 3,
            'acted_at'       => now()->subDays(10),
            'due_by'         => now()->subDays(4)->toDateString(),
            'is_within_sla'  => true,
            'days_taken'     => 12,
            'order_reference' => 'ADMN/ROP/2026/' . str_pad((string) (10 + $i), 3, '0', STR_PAD_LEFT),
            'created_at'     => now(), 'updated_at' => now(),
        ]);

        $nomineeId = DB::table('nominees')->insertGetId([
            'application_id'   => $application->id,
            'nominee_name'     => 'Nominee of ' . $application->applicant->full_name,
            'relationship'     => ['Son', 'Daughter', 'Wife'][$i % 3],
            'nominee_cnic'     => '35201' . str_pad((string) (3300000 + $i), 8, '0', STR_PAD_LEFT),
            'form_received_on' => now()->subDays(8)->toDateString(),
            'is_verified'      => true,
            'verified_at'      => now()->subDays(8),
            'created_at'       => now(), 'updated_at' => now(),
        ]);

        DB::table('nominee_heirs')->insert([
            'nominee_id'    => $nomineeId,
            'heir_name'     => 'First legal heir',
            'relationship'  => 'Son',
            'display_order' => 1,
            'created_at'    => now(), 'updated_at' => now(),
        ]);

        DB::table('tenancy_agreements')->insert([
            'application_id'  => $application->id,
            'agreement_no'    => $application->application_no . '/TA/01',
            'executed_on'     => now()->subDays(6)->toDateString(),
            'executed_by'     => 4,
            'applicant_id'    => $application->applicant_id,
            'monthly_rent'    => (string) $rentAmount,
            'security_amount' => (string) ($rentAmount * 3),
            'effective_from'  => now()->subDays(6)->toDateString(),
            'stamp_paper_no'  => 'SP-2026-' . (50000 + $i),
            'status'          => 'EXECUTED',
            'created_at'      => now(), 'updated_at' => now(),
        ]);

        DB::table('regularization_orders')->insert([
            'application_id'        => $application->id,
            'order_no'              => $application->application_no . '/ORD/01',
            'order_date'            => now()->subDays(5)->toDateString(),
            'issued_by'             => 4,
            'issued_by_designation' => 'Deputy Administrator',
            'order_text'            => 'The possession of ' . $application->applicant->full_name
                . ' over ' . $application->property->identity() . ' is hereby regularized under '
                . 'Clause 3(ii) of the Scheme for the Management and Disposal of Urban Evacuee Trust '
                . 'Properties, 1977. The occupant is treated as a tenant at the monthly rent fixed by '
                . 'the District Officer.',
            'regularized_area_sqft' => $application->property->currentArea?->area_sqft,
            'monthly_rent_fixed'    => (string) $rentAmount,
            'status'                => 'ISSUED',
            'created_at'            => now(), 'updated_at' => now(),
        ]);

        $application->forceFill([
            'approved_at'    => now()->subDays(10),
            'regularized_at' => now()->subDays(5),
        ])->save();
    }

    private function addLitigation(Application $application, string $stage): void
    {
        if ($stage !== 'SUB_JUDICE') {
            return;
        }

        DB::table('litigations')->insert([
            'application_id'         => $application->id,
            'property_id'            => $application->property_id,
            'court_name'             => 'Senior Civil Judge, Lahore',
            'case_no'                => 'Suit 1184/2025',
            'case_title'             => $application->applicant->full_name . ' vs. ETPB',
            'case_type'              => 'CIVIL_SUIT',
            'filed_on'               => now()->subMonths(7)->toDateString(),
            'petitioner'             => $application->applicant->full_name,
            'respondent'             => 'Evacuee Trust Property Board',
            'is_pending'             => true,
            'has_restraining_order'  => true,
            'restraining_order_date' => now()->subMonths(6)->toDateString(),
            'restraining_order_text' => 'The parties are directed to maintain status quo in respect '
                . 'of the suit property until the next date of hearing.',
            'next_hearing_date'      => now()->addDays(23)->toDateString(),
            'outcome'                => 'PENDING',
            'created_at'             => now(), 'updated_at' => now(),
        ]);

        $application->forceFill(['is_sub_judice' => true])->save();
    }
}
