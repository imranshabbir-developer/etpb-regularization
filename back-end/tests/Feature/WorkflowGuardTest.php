<?php

namespace Tests\Feature;

use App\Services\ArrearsService;
use App\Services\EligibilityService;
use App\Services\RentAssessmentService;
use App\Services\WorkflowService;
use Database\Seeders\GeographySeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * The workflow guards, which are where the Scheme actually bites.
 *
 * Each guard corresponds to a condition the Scheme imposes, and the test names
 * name the clause rather than the code path, because the clause is what has to
 * hold if a regularization is ever challenged.
 */
class WorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowService $workflow;
    private int $applicationId;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(GeographySeeder::class);

        $this->workflow = app(WorkflowService::class);
        $this->applicationId = $this->makeApplication('1998-04-12');
    }

    // ---- Clause 3(ii)(a): the possession cut-off ------------------------

    public function test_possession_before_the_cutoff_is_eligible(): void
    {
        $result = app(EligibilityService::class)->assess('2009-12-31');

        $this->assertTrue($result['is_eligible']);
        $this->assertStringContainsString('Clause 3(ii)(a)', $result['reason']);
    }

    public function test_possession_on_or_after_1_january_2010_is_not_eligible(): void
    {
        $result = app(EligibilityService::class)->assess('2010-01-01');

        $this->assertFalse($result['is_eligible']);
        $this->assertStringContainsString('prior to 01-01-2010', $result['reason']);
    }

    public function test_scrutiny_cannot_advance_when_the_applicant_is_ineligible(): void
    {
        $id = $this->makeApplication('2011-06-01', WorkflowService::SCRUTINY);

        $check = $this->workflow->check($id, WorkflowService::SITE_INSPECTION);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Clause 3(ii)(a)', implode(' ', $check['reasons']));
    }

    // ---- Clause 3(ii)(b): arrears run from the earliest of three dates ---

    public function test_arrears_run_from_the_earliest_of_the_three_candidate_dates(): void
    {
        $svc = app(EligibilityService::class);

        // Occupation in 1998 predates the statutory 01-07-2000 base date.
        $a = $svc->arrearsFrom('1998-04-12');
        $this->assertSame('1998-04-12', $a['date']);
        $this->assertSame('DATE_OF_OCCUPATION', $a['basis']);

        // Occupation in 2005 is later, so the statutory date governs.
        $b = $svc->arrearsFrom('2005-08-01');
        $this->assertSame('2000-07-01', $b['date']);
        $this->assertSame('STATUTORY_2000', $b['basis']);

        // A still earlier judicial verdict overrides both.
        $c = $svc->arrearsFrom('2005-08-01', '1996-02-20');
        $this->assertSame('1996-02-20', $c['date']);
        $this->assertSame('JUDICIAL_VERDICT', $c['basis']);
    }

    // ---- Submission guards ----------------------------------------------

    public function test_submission_is_blocked_without_the_processing_fee(): void
    {
        $check = $this->workflow->check($this->applicationId, WorkflowService::SUBMITTED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Rs. 5,000', implode(' ', $check['reasons']));
        $this->assertStringContainsString('Chairman ETPB', implode(' ', $check['reasons']));
    }

    public function test_submission_is_blocked_while_mandatory_evidence_is_missing(): void
    {
        $this->recordFee();

        $check = $this->workflow->check($this->applicationId, WorkflowService::SUBMITTED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Mandatory evidence is missing', implode(' ', $check['reasons']));
        $this->assertStringContainsString('Jamabandi', implode(' ', $check['reasons']));
    }

    public function test_submission_succeeds_once_fee_and_evidence_are_on_record(): void
    {
        $this->recordFee();
        $this->uploadMandatoryDocuments();

        $check = $this->workflow->check($this->applicationId, WorkflowService::SUBMITTED);
        $this->assertTrue($check['allowed'], implode(' ', $check['reasons']));

        $this->workflow->transition($this->applicationId, WorkflowService::SUBMITTED);

        $this->assertDatabaseHas('applications', [
            'id'     => $this->applicationId,
            'status' => WorkflowService::SUBMITTED,
        ]);
        $this->assertDatabaseHas('application_status_history', [
            'application_id' => $this->applicationId,
            'to_status'      => WorkflowService::SUBMITTED,
        ]);
    }

    // ---- An undeclared transition is refused outright --------------------

    public function test_a_transition_outside_the_declared_graph_is_refused(): void
    {
        $check = $this->workflow->check($this->applicationId, WorkflowService::REGULARIZED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('cannot move to', implode(' ', $check['reasons']));
    }

    public function test_transition_throws_when_a_guard_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->workflow->transition($this->applicationId, WorkflowService::SUBMITTED);
    }

    // ---- Clause 10(i)(c)-(d): notice, objection window, reasoned order ----

    public function test_rent_cannot_be_fixed_before_a_notice_has_issued(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::OBJECTION_WINDOW]);

        $check = $this->workflow->check($this->applicationId, WorkflowService::RENT_FIXED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Clause 10(i)(b)', implode(' ', $check['reasons']));
    }

    public function test_rent_cannot_be_fixed_while_the_15_day_objection_window_is_open(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::OBJECTION_WINDOW]);

        $this->issueNotice(now()->addDays(10));   // deadline still in the future

        $check = $this->workflow->check($this->applicationId, WorkflowService::RENT_FIXED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('objection window is still open', implode(' ', $check['reasons']));
    }

    public function test_rent_cannot_be_fixed_while_an_objection_is_undecided(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::OBJECTION_WINDOW]);

        $noticeId = $this->issueNotice(now()->subDay());

        DB::table('objections')->insert([
            'application_id'   => $this->applicationId,
            'public_notice_id' => $noticeId,
            'objection_no'     => 'OBJ/1',
            'objector_name'    => 'Bashir Ahmed',
            'plea'             => 'The applicant was not in possession before 2010.',
            'filed_on'         => now()->subDays(3)->toDateString(),
            'status'           => 'FILED',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $check = $this->workflow->check($this->applicationId, WorkflowService::RENT_FIXED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Clause 10(i)(d)', implode(' ', $check['reasons']));
    }

    public function test_rent_cannot_be_fixed_without_a_reasoned_determination(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::OBJECTION_WINDOW]);
        $this->issueNotice(now()->subDay());

        $check = $this->workflow->check($this->applicationId, WorkflowService::RENT_FIXED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('has not recorded a determination', implode(' ', $check['reasons']));
    }

    // ---- Clause 3(ii)(b): arrears must be cleared before approval ---------

    public function test_approval_is_blocked_while_arrears_remain_outstanding(): void
    {
        $this->buildAssessmentAndArrears();

        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::ARREARS_COMPUTED]);

        $check = $this->workflow->check($this->applicationId, WorkflowService::PENDING_ADMIN_APPROVAL);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('Clause 3(ii)(b)', implode(' ', $check['reasons']));
    }

    public function test_an_approved_instalment_plan_satisfies_the_arrears_condition(): void
    {
        $this->buildAssessmentAndArrears();

        $planId = app(ArrearsService::class)
            ->proposeInstalmentPlan($this->applicationId, 24, now()->toDateString());
        DB::table('instalment_plans')->where('id', $planId)->update(['status' => 'APPROVED']);

        $status = app(ArrearsService::class)->clearanceStatus($this->applicationId);

        $this->assertTrue($status['satisfied']);
        $this->assertStringContainsString('Clause 13', $status['reason']);
    }

    public function test_an_instalment_plan_may_not_exceed_24_instalments(): void
    {
        $this->buildAssessmentAndArrears();

        $this->expectException(\InvalidArgumentException::class);
        app(ArrearsService::class)
            ->proposeInstalmentPlan($this->applicationId, 36, now()->toDateString());
    }

    // ---- Litigation gate --------------------------------------------------

    public function test_a_restraining_order_blocks_the_application(): void
    {
        DB::table('litigations')->insert([
            'application_id'         => $this->applicationId,
            'court_name'             => 'Civil Court, Lahore',
            'case_no'                => 'Suit 442/2021',
            'case_type'              => 'CIVIL_SUIT',
            'is_pending'             => true,
            'has_restraining_order'  => true,
            'restraining_order_date' => now()->subMonths(3)->toDateString(),
            'outcome'                => 'PENDING',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::SITE_INSPECTION]);

        $check = $this->workflow->check($this->applicationId, WorkflowService::ASSESSMENT_PROPOSED);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('sub judice', implode(' ', $check['reasons']));
    }

    // ---- Scheme para 3(iii)(B): the nomination form gates regularization --

    public function test_agreement_execution_is_blocked_without_the_nomination_form(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::APPROVED]);

        $check = $this->workflow->check($this->applicationId, WorkflowService::AGREEMENT_EXECUTION);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('nomination form', implode(' ', $check['reasons']));
    }

    // ---- Clause 10(i)(e) / 3(ii)(d): SLA clocks start on transition -------

    public function test_issuing_a_notice_starts_the_60_day_assessment_clock(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::ASSESSMENT_PROPOSED, 'payment_status' => 'PAID']);

        $this->workflow->transition($this->applicationId, WorkflowService::NOTICE_ISSUED);

        $app = DB::table('applications')->find($this->applicationId);

        $this->assertSame(now()->toDateString(), $app->first_notice_date);
        $this->assertSame(now()->addDays(60)->toDateString(), $app->assessment_due_date);
    }

    // ---- The Rs. 5,000 gate -----------------------------------------------

    /**
     * The client's rule: "if the amount is not paid then the application status
     * will be marked as pending and the application... will not be process[ed]".
     */
    public function test_an_unpaid_application_cannot_be_processed(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::FEE_VERIFICATION, 'payment_status' => 'PENDING']);

        $check = $this->workflow->check($this->applicationId, WorkflowService::SCRUTINY);

        $this->assertFalse($check['allowed']);
        $this->assertStringContainsString('PENDING', implode(' ', $check['reasons']));
        $this->assertStringContainsString('Rs. 5,000', implode(' ', $check['reasons']));
        $this->assertStringContainsString('Chairman ETPB', implode(' ', $check['reasons']));
    }

    public function test_confirming_the_deposit_releases_the_application(): void
    {
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::FEE_VERIFICATION, 'payment_status' => 'PAID']);

        $check = $this->workflow->check($this->applicationId, WorkflowService::SCRUTINY);

        $this->assertTrue($check['allowed'], implode(' ', $check['reasons']));
    }

    /**
     * The gate is not merely on the first step. Every departmental stage refuses
     * an unpaid file, so there is no way past it further down the chain.
     */
    public function test_every_processing_stage_refuses_an_unpaid_application(): void
    {
        foreach (WorkflowService::PROCESSING_STATES as $state) {
            DB::table('applications')->where('id', $this->applicationId)->update([
                'payment_status' => 'PENDING',
                // Place the application immediately before the stage under test
                // so only the payment guard can be what refuses it.
                'status' => collect(WorkflowService::graph())
                    ->filter(fn ($targets) => in_array($state, $targets, true))
                    ->keys()->first() ?? WorkflowService::DRAFT,
            ]);

            $check = $this->workflow->check($this->applicationId, $state);

            $this->assertFalse($check['allowed'], "State {$state} was allowed while payment was pending.");
        }
    }

    public function test_a_draft_may_still_be_prepared_before_payment(): void
    {
        // Filing and completing the form is not "processing"; only the
        // department's own steps are gated.
        DB::table('applications')->where('id', $this->applicationId)
            ->update(['status' => WorkflowService::DRAFT, 'payment_status' => 'PENDING']);

        $this->recordFee('PENDING');
        $this->uploadMandatoryDocuments();

        $check = $this->workflow->check($this->applicationId, WorkflowService::SUBMITTED);

        $this->assertTrue($check['allowed'], implode(' ', $check['reasons']));
    }

    // ---- fixtures ---------------------------------------------------------

    private function makeApplication(string $possessionDate, string $status = WorkflowService::DRAFT): int
    {
        $districtId = DB::table('districts')->where('name', 'Lahore')->value('id');
        $provinceId = DB::table('districts')->where('id', $districtId)->value('province_id');
        $profileId  = DB::table('unit_conversion_profiles')->where('code', 'REVENUE')->value('id');

        $applicantId = DB::table('applicants')->insertGetId([
            'full_name'      => 'Ram Lal',
            'parentage_type' => 'FATHER',
            'parentage_name' => 'Diwan Chand',
            'cnic'           => '3520112349876',
            'contact'        => '0300-4455667',
            'postal_address' => 'House 14-B, Krishan Nagar, Lahore',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $propertyId = DB::table('properties')->insertGetId([
            'property_no'   => 'KN-14-B',
            'property_type' => 'HOUSE',
            'usage_type'    => 'RESIDENTIAL',
            'address'       => '14-B Krishan Nagar, Lahore',
            'province_id'   => $provinceId,
            'district_id'   => $districtId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('property_areas')->insert([
            'property_id'     => $propertyId,
            'unit_profile_id' => $profileId,
            'entry_mode'      => 'COMPOUND',
            'kanals'          => 2,
            'marlas'          => 7,
            'sarsais'         => 3,
            'area_sqft'       => '12886.5000',
            'is_current'      => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $applicationId = DB::table('applications')->insertGetId([
            'application_no'  => 'ETPB/TEST/ROP/2026/' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'applicant_id'    => $applicantId,
            'property_id'     => $propertyId,
            'district_id'     => $districtId,
            'unit_profile_id' => $profileId,
            'status'          => $status,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $assessment = app(EligibilityService::class)->assess($possessionDate);

        DB::table('possession_details')->insert([
            'application_id'     => $applicationId,
            'date_of_possession' => $possessionDate,
            'possession_nature'  => 'INHERITED',
            'arrears_from'       => $assessment['arrears_from'],
            'arrears_from_basis' => $assessment['arrears_from_basis'],
            'is_eligible'        => $assessment['is_eligible'],
            'eligibility_reason' => $assessment['reason'],
            'cutoff_applied'     => $assessment['cutoff_applied'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $applicationId;
    }

    private function recordFee(string $status = 'VERIFIED'): void
    {
        DB::table('fee_payments')->insert([
            'application_id'    => $this->applicationId,
            'instrument_type'   => 'PAY_ORDER',
            'instrument_no'     => 'PO-99881',
            'instrument_date'   => now()->subDays(5)->toDateString(),
            'amount'            => '5000.00',
            'payee'             => 'Chairman ETPB',
            'bank_name'         => 'National Bank of Pakistan',
            'branch_name'       => 'Mall Road, Lahore',
            'branch_code'       => '0123',
            'depositor_name'    => 'Ram Lal',
            'depositor_cnic'    => '3520112349876',
            'depositor_contact' => '0300-4455667',
            'submission_date'   => now()->subDays(5)->toDateString(),
            'status'            => $status,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    private function uploadMandatoryDocuments(): void
    {
        $types = DB::table('document_types')->where('is_mandatory', true)->get();

        foreach ($types as $t) {
            DB::table('application_documents')->insert([
                'application_id'    => $this->applicationId,
                'document_type_id'  => $t->id,
                'title'             => $t->name,
                'file_path'         => 'uploads/test/' . $t->code . '.pdf',
                'original_filename' => $t->code . '.pdf',
                'mime_type'         => 'application/pdf',
                'size_bytes'        => 1024,
                'sha256'            => hash('sha256', $t->code),
                'is_certified_copy' => (bool) $t->is_certified_copy_required,
                'status'            => 'PENDING',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    private function issueNotice(\DateTimeInterface $deadline): int
    {
        return DB::table('public_notices')->insertGetId([
            'application_id'     => $this->applicationId,
            'notice_no'          => 'NOT/' . random_int(1000, 9999),
            'notice_type'        => 'PUBLIC',
            'issued_on'          => now()->subDays(16)->toDateString(),
            'service_mode'       => 'NOTICE_BOARD',
            'objection_deadline' => $deadline->format('Y-m-d'),
            'status'             => 'ISSUED',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function buildAssessmentAndArrears(): void
    {
        $app = DB::table('applications')->find($this->applicationId);

        $roundId = DB::table('assessment_rounds')->insertGetId([
            'application_id'           => $this->applicationId,
            'property_id'              => $app->property_id,
            'round_no'                 => 1,
            'round_type'               => 'INITIAL',
            'base_date'                => '2006-07-01',
            'effective_from'           => '2006-07-01',
            'enhancement_rate'         => '8.00',
            'enhancement_method'       => 'COMPOUND',
            'reassessment_cycle_years' => 6,
            'status'                   => 'DECIDED',
            'determined_monthly_rent'  => '1000.00',
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        app(RentAssessmentService::class)->generateSchedule($roundId);
        app(ArrearsService::class)->generate($this->applicationId);
    }
}
