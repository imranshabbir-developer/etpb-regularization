<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The application state machine.
 *
 * Transitions are declared, not scattered through controllers, and each one
 * carries the guard the Scheme imposes on it. A guard failure returns the
 * statutory reason, so the officer sees which clause blocked the move rather
 * than a generic error.
 */
class WorkflowService
{
    public const DRAFT                   = 'DRAFT';
    public const SUBMITTED               = 'SUBMITTED';
    public const FEE_VERIFICATION        = 'FEE_VERIFICATION';
    public const SCRUTINY                = 'SCRUTINY';
    public const RETURNED_DEFICIENT      = 'RETURNED_DEFICIENT';
    public const REJECTED_INELIGIBLE     = 'REJECTED_INELIGIBLE';
    public const SITE_INSPECTION         = 'SITE_INSPECTION';
    public const SUB_JUDICE              = 'SUB_JUDICE';
    public const ASSESSMENT_PROPOSED     = 'ASSESSMENT_PROPOSED';
    public const NOTICE_ISSUED           = 'NOTICE_ISSUED';
    public const OBJECTION_WINDOW        = 'OBJECTION_WINDOW';
    public const HEARING                 = 'HEARING';
    public const RENT_FIXED              = 'RENT_FIXED';
    public const ARREARS_COMPUTED        = 'ARREARS_COMPUTED';
    public const PENDING_ADMIN_APPROVAL  = 'PENDING_ADMIN_APPROVAL';
    public const APPROVED                = 'APPROVED';
    public const REMANDED                = 'REMANDED';
    public const REJECTED                = 'REJECTED';
    public const AGREEMENT_EXECUTION     = 'AGREEMENT_EXECUTION';
    public const REGULARIZED             = 'REGULARIZED';

    /** Human labels for the UI. */
    public const LABELS = [
        self::DRAFT                  => 'Draft',
        self::SUBMITTED              => 'Submitted',
        self::FEE_VERIFICATION       => 'Fee Verification',
        self::SCRUTINY               => 'Under Scrutiny',
        self::RETURNED_DEFICIENT     => 'Returned (Deficient)',
        self::REJECTED_INELIGIBLE    => 'Rejected (Ineligible)',
        self::SITE_INSPECTION        => 'Site Inspection',
        self::SUB_JUDICE             => 'Sub Judice',
        self::ASSESSMENT_PROPOSED    => 'Assessment Proposed',
        self::NOTICE_ISSUED          => 'Notice Issued',
        self::OBJECTION_WINDOW       => 'Objection Window Open',
        self::HEARING                => 'Hearing',
        self::RENT_FIXED             => 'Rent Fixed',
        self::ARREARS_COMPUTED       => 'Arrears Computed',
        self::PENDING_ADMIN_APPROVAL => 'Pending Administrator Approval',
        self::APPROVED               => 'Approved',
        self::REMANDED               => 'Remanded',
        self::REJECTED               => 'Rejected',
        self::AGREEMENT_EXECUTION    => 'Agreement Execution',
        self::REGULARIZED            => 'Regularized',
    ];

    /** Badge colour class per status, used by the views. */
    public const TONES = [
        self::DRAFT                  => 'neutral',
        self::SUBMITTED              => 'info',
        self::FEE_VERIFICATION       => 'info',
        self::SCRUTINY               => 'info',
        self::RETURNED_DEFICIENT     => 'warn',
        self::REJECTED_INELIGIBLE    => 'danger',
        self::SITE_INSPECTION        => 'info',
        self::SUB_JUDICE             => 'warn',
        self::ASSESSMENT_PROPOSED    => 'info',
        self::NOTICE_ISSUED          => 'info',
        self::OBJECTION_WINDOW       => 'warn',
        self::HEARING                => 'warn',
        self::RENT_FIXED             => 'good',
        self::ARREARS_COMPUTED       => 'good',
        self::PENDING_ADMIN_APPROVAL => 'warn',
        self::APPROVED               => 'good',
        self::REMANDED               => 'warn',
        self::REJECTED               => 'danger',
        self::AGREEMENT_EXECUTION    => 'good',
        self::REGULARIZED            => 'good',
    ];

    public function __construct(
        private readonly SettingService $settings,
        private readonly ArrearsService $arrears,
    ) {
    }

    /**
     * Allowed target states from a given state.
     *
     * @return array<int, string>
     */
    public function allowedFrom(string $status): array
    {
        return self::graph()[$status] ?? [];
    }

    /** @return array<string, array<int, string>> */
    public static function graph(): array
    {
        return [
            self::DRAFT               => [self::SUBMITTED],
            self::SUBMITTED           => [self::FEE_VERIFICATION, self::RETURNED_DEFICIENT],
            self::FEE_VERIFICATION    => [self::SCRUTINY, self::RETURNED_DEFICIENT],
            self::SCRUTINY            => [self::SITE_INSPECTION, self::RETURNED_DEFICIENT, self::REJECTED_INELIGIBLE],
            self::RETURNED_DEFICIENT  => [self::SUBMITTED],
            self::SITE_INSPECTION     => [self::ASSESSMENT_PROPOSED, self::SUB_JUDICE],
            self::SUB_JUDICE          => [self::SITE_INSPECTION, self::REJECTED],
            self::ASSESSMENT_PROPOSED => [self::NOTICE_ISSUED],
            self::NOTICE_ISSUED       => [self::OBJECTION_WINDOW],
            self::OBJECTION_WINDOW    => [self::HEARING, self::RENT_FIXED],
            self::HEARING             => [self::RENT_FIXED],
            self::RENT_FIXED          => [self::ARREARS_COMPUTED],
            self::ARREARS_COMPUTED    => [self::PENDING_ADMIN_APPROVAL],
            self::PENDING_ADMIN_APPROVAL => [self::APPROVED, self::REJECTED, self::REMANDED],
            self::REMANDED            => [self::ASSESSMENT_PROPOSED],
            self::APPROVED            => [self::AGREEMENT_EXECUTION],
            self::AGREEMENT_EXECUTION => [self::REGULARIZED],
            self::REGULARIZED         => [],
            self::REJECTED            => [],
            self::REJECTED_INELIGIBLE => [],
        ];
    }

    /**
     * Check a transition without performing it.
     *
     * @return array{allowed: bool, reasons: array<int, string>}
     */
    public function check(int $applicationId, string $to): array
    {
        $app = $this->application($applicationId);
        $reasons = [];

        if (! in_array($to, $this->allowedFrom($app->status), true)) {
            return [
                'allowed' => false,
                'reasons' => [sprintf(
                    'An application in state "%s" cannot move to "%s".',
                    self::LABELS[$app->status] ?? $app->status,
                    self::LABELS[$to] ?? $to
                )],
            ];
        }

        foreach ($this->guardsFor($to) as $guard) {
            $result = $guard($app);
            if ($result !== null) {
                $reasons[] = $result;
            }
        }

        return ['allowed' => $reasons === [], 'reasons' => $reasons];
    }

    /**
     * Perform a transition, recording it in the status history.
     */
    public function transition(
        int $applicationId,
        string $to,
        ?int $actorId = null,
        ?string $actorRole = null,
        ?string $remarks = null,
        ?string $ip = null,
    ): void {
        $check = $this->check($applicationId, $to);

        if (! $check['allowed']) {
            throw new RuntimeException(implode(' ', $check['reasons']));
        }

        DB::transaction(function () use ($applicationId, $to, $actorId, $actorRole, $remarks, $ip) {
            $app = $this->application($applicationId);

            $update = [
                'previous_status' => $app->status,
                'status'          => $to,
                'status_remarks'  => $remarks,
                'updated_by'      => $actorId,
                'updated_at'      => now(),
            ];

            $update += $this->sideEffects($to, $app);

            DB::table('applications')->where('id', $applicationId)->update($update);

            DB::table('application_status_history')->insert([
                'application_id' => $applicationId,
                'from_status'    => $app->status,
                'to_status'      => $to,
                'action'         => $to,
                'remarks'        => $remarks,
                'actor_id'       => $actorId,
                'actor_role'     => $actorRole,
                'ip_address'     => $ip,
                'occurred_at'    => now(),
            ]);
        });
    }

    /**
     * Timestamps and SLA clocks that a transition starts or stops.
     *
     * @return array<string, mixed>
     */
    private function sideEffects(string $to, object $app): array
    {
        return match ($to) {
            self::SUBMITTED => ['submitted_at' => now()],
            self::SCRUTINY  => ['scrutiny_started_at' => now()],

            // Clause 10(i)(e): 60 days from the first notice.
            self::NOTICE_ISSUED => [
                'first_notice_date'    => now()->toDateString(),
                'assessment_due_date'  => now()->addDays($this->settings->int('assessment_sla_days', 60))->toDateString(),
            ],

            self::RENT_FIXED => ['rent_fixed_at' => now()],

            // Clause 3(ii)(d): the Administrator approves within one month.
            self::PENDING_ADMIN_APPROVAL => [
                'admin_approval_due_date' => now()->addDays($this->settings->int('admin_approval_sla_days', 30))->toDateString(),
            ],

            self::APPROVED    => ['approved_at' => now()],
            self::REGULARIZED => ['regularized_at' => now()],
            self::SUB_JUDICE  => ['is_sub_judice' => true],
            self::SITE_INSPECTION => ['is_sub_judice' => false],

            self::REJECTED, self::REJECTED_INELIGIBLE => ['rejected_at' => now()],

            default => [],
        };
    }

    /**
     * @return array<int, callable(object): ?string>
     */
    private function guardsFor(string $to): array
    {
        $guards = match ($to) {
            self::SUBMITTED               => [$this->feeRecorded(), $this->mandatoryDocuments()],
            self::SCRUTINY                => [$this->paymentConfirmed()],
            self::SITE_INSPECTION         => [$this->eligible()],
            self::ASSESSMENT_PROPOSED     => [$this->notSubJudice()],
            self::RENT_FIXED              => [$this->objectionWindowClosed(), $this->reasonedDetermination()],
            self::ARREARS_COMPUTED        => [$this->rentScheduleExists()],
            self::PENDING_ADMIN_APPROVAL  => [$this->arrearsSatisfied(), $this->notSubJudice()],
            self::APPROVED                => [$this->administratorReasons()],
            self::AGREEMENT_EXECUTION     => [$this->nomineeFormOnRecord()],
            default                       => [],
        };

        // "the application which payment is not made by the applicant the same
        // application will not be process" — so every departmental step beyond
        // fee verification is refused outright while the fee is unpaid, not
        // merely the first one.
        if (in_array($to, self::PROCESSING_STATES, true)) {
            array_unshift($guards, $this->paymentConfirmed());
        }

        return $guards;
    }

    /**
     * States that constitute the department processing the application.
     * Reaching any of them requires the Rs. 5,000 to be confirmed as paid.
     */
    public const PROCESSING_STATES = [
        self::SCRUTINY,
        self::SITE_INSPECTION,
        self::ASSESSMENT_PROPOSED,
        self::NOTICE_ISSUED,
        self::OBJECTION_WINDOW,
        self::HEARING,
        self::RENT_FIXED,
        self::ARREARS_COMPUTED,
        self::PENDING_ADMIN_APPROVAL,
        self::APPROVED,
        self::AGREEMENT_EXECUTION,
        self::REGULARIZED,
    ];

    // ---- guards -------------------------------------------------------

    private function feeRecorded(): callable
    {
        return function (object $app): ?string {
            $fee = DB::table('fee_payments')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->first();

            if (! $fee) {
                $amount = $this->settings->decimal('processing_fee', '5000.00');

                return sprintf(
                    'The processing fee instrument of Rs. %s in favour of Chairman ETPB has not been recorded.',
                    number_format((float) $amount, 0)
                );
            }

            return null;
        };
    }

    /**
     * The gate the whole scheme turns on. Until Accounts confirms the deposit
     * the application sits at "pending" and the department does not touch it.
     */
    private function paymentConfirmed(): callable
    {
        return function (object $app): ?string {
            if ($app->payment_status === 'PAID') {
                return null;
            }

            $fee = $this->settings->decimal('processing_fee', '5000.00');

            return sprintf(
                'Payment is still PENDING. The application cannot be processed until the '
                . 'deposit of Rs. %s in favour of Chairman ETPB is confirmed by Accounts.',
                number_format((float) $fee, 0)
            );
        };
    }

    private function mandatoryDocuments(): callable
    {
        return static function (object $app): ?string {
            $required = DB::table('document_types')
                ->where('is_mandatory', true)
                ->where('is_active', true)
                ->pluck('name', 'id');

            $present = DB::table('application_documents')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->whereIn('status', ['PENDING', 'VERIFIED', 'WAIVED'])
                ->pluck('document_type_id')
                ->unique();

            $missing = $required->except($present->all());

            return $missing->isEmpty()
                ? null
                : 'Mandatory evidence is missing: ' . $missing->implode(', ') . '.';
        };
    }

    private function eligible(): callable
    {
        return static function (object $app): ?string {
            $p = DB::table('possession_details')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->first();

            if (! $p) {
                return 'Possession details have not been recorded.';
            }

            return $p->is_eligible
                ? null
                : 'The application is ineligible under Clause 3(ii)(a): ' . $p->eligibility_reason;
        };
    }

    private function notSubJudice(): callable
    {
        return static function (object $app): ?string {
            $blocked = DB::table('litigations')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('is_pending', true)->orWhere('has_restraining_order', true);
                })
                ->exists();

            return $blocked
                ? 'The property is sub judice — a case is pending or a restraining order is in force. '
                  . 'The application cannot proceed until the stay is vacated or the case is disposed of.'
                : null;
        };
    }

    private function objectionWindowClosed(): callable
    {
        return function (object $app): ?string {
            $notice = DB::table('public_notices')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->orderByDesc('issued_on')
                ->first();

            if (! $notice) {
                return 'No notice has been issued. Clause 10(i)(b) and (c) require the proposed '
                     . 'assessment to be published and 15 days allowed for objections.';
            }

            $undecided = DB::table('objections')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->whereIn('status', ['FILED', 'UNDER_HEARING'])
                ->count();

            if ($undecided > 0) {
                return sprintf(
                    '%d objection(s) are still undecided. Clause 10(i)(d) requires the rent to be '
                    . 'fixed only after an opportunity of hearing to the tenant and objectors.',
                    $undecided
                );
            }

            if (Carbon::parse($notice->objection_deadline)->isFuture()) {
                return sprintf(
                    'The 15-day objection window is still open until %s (Clause 10(i)(c)).',
                    Carbon::parse($notice->objection_deadline)->format('d-m-Y')
                );
            }

            return null;
        };
    }

    private function reasonedDetermination(): callable
    {
        return static function (object $app): ?string {
            $decision = DB::table('assessment_decisions as ad')
                ->join('assessment_rounds as ar', 'ar.id', '=', 'ad.assessment_round_id')
                ->where('ar.application_id', $app->id)
                ->whereNull('ad.deleted_at')
                ->where('ad.is_superseded', false)
                ->orderByDesc('ad.decided_at')
                ->first();

            if (! $decision) {
                return 'The District Officer has not recorded a determination of rent.';
            }

            if (trim((string) $decision->reasons) === '') {
                return 'Clause 10(i)(d) requires the fixation of rent to be reasoned. '
                     . 'No reasons have been recorded.';
            }

            return null;
        };
    }

    private function rentScheduleExists(): callable
    {
        return static function (object $app): ?string {
            $exists = DB::table('rent_schedules')->where('application_id', $app->id)->exists();

            return $exists ? null : 'No rent schedule has been generated for this application.';
        };
    }

    private function arrearsSatisfied(): callable
    {
        return function (object $app): ?string {
            $status = $this->arrears->clearanceStatus($app->id);

            return $status['satisfied'] ? null : $status['reason'];
        };
    }

    private function administratorReasons(): callable
    {
        return static function (object $app): ?string {
            $approval = DB::table('approvals')
                ->where('application_id', $app->id)
                ->where('level', 'ADMINISTRATOR')
                ->where('action', 'APPROVE')
                ->whereNull('deleted_at')
                ->orderByDesc('acted_at')
                ->first();

            if (! $approval) {
                return 'The Administrator has not recorded an approval.';
            }

            if (trim((string) $approval->reasons) === '') {
                return 'Clause 3(ii)(d) requires the Administrator to record reasons for the approval.';
            }

            return null;
        };
    }

    private function nomineeFormOnRecord(): callable
    {
        return static function (object $app): ?string {
            $exists = DB::table('nominees')
                ->where('application_id', $app->id)
                ->whereNull('deleted_at')
                ->exists();

            return $exists
                ? null
                : 'The nomination form has not been obtained. Under the proviso to Scheme para 3(iii)(B) '
                  . 'the District Officer shall not regularize the possession until it is on record.';
        };
    }

    private function application(int $id): object
    {
        $app = DB::table('applications')->where('id', $id)->whereNull('deleted_at')->first();

        if (! $app) {
            throw new RuntimeException("Application [{$id}] not found.");
        }

        return $app;
    }
}
