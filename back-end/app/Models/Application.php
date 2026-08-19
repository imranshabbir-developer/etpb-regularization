<?php

namespace App\Models;

use App\Services\WorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * An application by an existing occupant to have possession regularized
 * under Clause 3(ii) of the Scheme 1977.
 */
class Application extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'submitted_at'            => 'datetime',
            'scrutiny_started_at'     => 'datetime',
            'rent_fixed_at'           => 'datetime',
            'approved_at'             => 'datetime',
            'regularized_at'          => 'datetime',
            'rejected_at'             => 'datetime',
            'first_notice_date'       => 'date',
            'assessment_due_date'     => 'date',
            'assessment_extended_to'  => 'date',
            'admin_approval_due_date' => 'date',
            'is_sub_judice'           => 'boolean',
            'is_locked'               => 'boolean',
        ];
    }

    // ---- relations ----------------------------------------------------

    public function applicant(): BelongsTo    { return $this->belongsTo(Applicant::class); }
    public function property(): BelongsTo     { return $this->belongsTo(Property::class); }
    public function district(): BelongsTo     { return $this->belongsTo(District::class); }
    public function office(): BelongsTo       { return $this->belongsTo(Office::class); }
    public function unitProfile(): BelongsTo  { return $this->belongsTo(UnitConversionProfile::class, 'unit_profile_id'); }
    public function districtOfficer(): BelongsTo { return $this->belongsTo(User::class, 'assigned_do_id'); }
    public function administrator(): BelongsTo   { return $this->belongsTo(User::class, 'assigned_admin_id'); }

    public function possession(): HasOne      { return $this->hasOne(PossessionDetail::class); }

    public function documents(): HasMany      { return $this->hasMany(ApplicationDocument::class); }
    public function feePayments(): HasMany    { return $this->hasMany(FeePayment::class); }
    public function rounds(): HasMany         { return $this->hasMany(AssessmentRound::class); }
    public function rentSchedules(): HasMany  { return $this->hasMany(RentSchedule::class); }
    public function ledger(): HasMany         { return $this->hasMany(ArrearsLedger::class); }
    public function receipts(): HasMany       { return $this->hasMany(PaymentReceipt::class); }
    public function notices(): HasMany        { return $this->hasMany(PublicNotice::class); }
    public function objections(): HasMany     { return $this->hasMany(Objection::class); }
    public function hearings(): HasMany       { return $this->hasMany(Hearing::class); }
    public function litigations(): HasMany    { return $this->hasMany(Litigation::class); }
    public function occupantOffers(): HasMany { return $this->hasMany(OccupantOffer::class); }
    public function approvals(): HasMany      { return $this->hasMany(Approval::class); }
    public function nominees(): HasMany       { return $this->hasMany(Nominee::class); }
    public function agreement(): HasOne       { return $this->hasOne(TenancyAgreement::class); }
    public function order(): HasOne           { return $this->hasOne(RegularizationOrder::class); }

    public function history(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderByDesc('occurred_at');
    }

    /** The round the current assessment is being run under. */
    public function currentRound(): HasOne
    {
        return $this->hasOne(AssessmentRound::class)->latestOfMany('round_no');
    }

    // ---- presentation --------------------------------------------------

    public function statusLabel(): string
    {
        return WorkflowService::LABELS[$this->status] ?? $this->status;
    }

    public function statusTone(): string
    {
        return WorkflowService::TONES[$this->status] ?? 'neutral';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            WorkflowService::REGULARIZED,
            WorkflowService::REJECTED,
            WorkflowService::REJECTED_INELIGIBLE,
        ], true);
    }

    // ---- statutory clocks ----------------------------------------------

    /**
     * Progress against the 60-day assessment deadline of Clause 10(i)(e).
     *
     * @return array{applies: bool, due: ?string, days_left: ?int, pct: int, tone: string, label: string}
     */
    public function assessmentSla(): array
    {
        $due = $this->assessment_extended_to ?: $this->assessment_due_date;

        if (! $this->first_notice_date || ! $due || $this->rent_fixed_at) {
            return ['applies' => false, 'due' => null, 'days_left' => null,
                    'pct' => 0, 'tone' => 'neutral', 'label' => 'Not started'];
        }

        return $this->clock($this->first_notice_date, $due, 'Assessment');
    }

    /**
     * Progress against the one-month Administrator deadline of Clause 3(ii)(d) —
     * the most frequently breached statutory deadline in this process.
     *
     * @return array{applies: bool, due: ?string, days_left: ?int, pct: int, tone: string, label: string}
     */
    public function adminApprovalSla(): array
    {
        if (! $this->admin_approval_due_date
            || $this->status !== WorkflowService::PENDING_ADMIN_APPROVAL) {
            return ['applies' => false, 'due' => null, 'days_left' => null,
                    'pct' => 0, 'tone' => 'neutral', 'label' => 'Not applicable'];
        }

        $from = $this->rent_fixed_at ? Carbon::parse($this->rent_fixed_at) : Carbon::parse($this->updated_at);

        return $this->clock($from, $this->admin_approval_due_date, 'Approval');
    }

    /**
     * @return array{applies: bool, due: string, days_left: int, pct: int, tone: string, label: string}
     */
    private function clock(Carbon|string $from, Carbon|string $due, string $what): array
    {
        $start = $from instanceof Carbon ? $from->copy() : Carbon::parse($from);
        $end   = $due instanceof Carbon ? $due->copy() : Carbon::parse($due);
        $today = Carbon::today();

        $total = max(1, $start->diffInDays($end));
        $used  = max(0, $start->diffInDays($today));
        $pct   = (int) min(100, round($used / $total * 100));

        $daysLeft = (int) $today->diffInDays($end, false);

        [$tone, $label] = match (true) {
            $daysLeft < 0  => ['danger', sprintf('%s overdue by %d day(s)', $what, abs($daysLeft))],
            $daysLeft <= 7 => ['warn',   sprintf('%s due in %d day(s)', $what, $daysLeft)],
            default        => ['good',   sprintf('%s due in %d day(s)', $what, $daysLeft)],
        };

        return [
            'applies'   => true,
            'due'       => $end->toDateString(),
            'days_left' => $daysLeft,
            'pct'       => $pct,
            'tone'      => $tone,
            'label'     => $label,
        ];
    }

    // ---- numbering ------------------------------------------------------

    /**
     * ETPB/{DISTRICT}/ROP/{YYYY}/{SEQ} — sequential per district, per year.
     */
    public static function nextApplicationNo(int $districtId): string
    {
        $district = District::find($districtId);
        $code = $district?->code ?: 'GEN';
        $year = now()->year;
        $prefix = sprintf('ETPB/%s/ROP/%d/', $code, $year);

        $seq = DB::table('applications')
            ->where('application_no', 'like', $prefix . '%')
            ->count() + 1;

        // Guard against a gap left by a soft-deleted row taking the number.
        do {
            $candidate = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $taken = DB::table('applications')->where('application_no', $candidate)->exists();
            $seq++;
        } while ($taken);

        return $candidate;
    }

    // ---- scopes ---------------------------------------------------------

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasPermission('applications.view_all')) {
            return $query;
        }

        if ($user->hasPermission('applications.view_district')) {
            return $query->where('district_id', $user->district_id);
        }

        // An applicant sees only their own.
        return $query->whereHas('applicant', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            WorkflowService::REGULARIZED,
            WorkflowService::REJECTED,
            WorkflowService::REJECTED_INELIGIBLE,
        ]);
    }
}
