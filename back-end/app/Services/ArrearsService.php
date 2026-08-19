<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The arrears ledger and its recovery.
 *
 * Clause 3(ii)(b) makes clearance of arrears a condition of being treated as a
 * tenant, so the ledger balance is what gates Administrator approval. Clause 12
 * lets the Chairman remit rent for the indigent, orphans and widows; Clause 13
 * lets the District Officer allow recovery in not more than 24 instalments.
 */
class ArrearsService
{
    public const MONEY_SCALE = 2;

    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    /**
     * Build the ledger from the rent schedule of the current assessment round.
     *
     * @return array{rows: int, total_due: string}
     */
    public function generate(int $applicationId): array
    {
        $schedule = DB::table('rent_schedules')
            ->where('application_id', $applicationId)
            ->orderBy('year')
            ->get();

        if ($schedule->isEmpty()) {
            throw new RuntimeException(
                'No rent schedule exists for this application. Fix the rent before generating arrears.'
            );
        }

        $existingPayments = DB::table('arrears_ledger')
            ->where('application_id', $applicationId)
            ->pluck('amount_paid', 'period_year');

        $existingRemissions = DB::table('arrears_ledger')
            ->where('application_id', $applicationId)
            ->pluck('remission_amount', 'period_year');

        $rows = [];
        $totalDue = '0';

        foreach ($schedule as $s) {
            $paid     = (string) ($existingPayments[$s->year] ?? '0');
            $remitted = (string) ($existingRemissions[$s->year] ?? '0');
            $due      = (string) $s->annual_rent;

            $balance = bcsub(bcsub($due, $paid, self::MONEY_SCALE), $remitted, self::MONEY_SCALE);
            $totalDue = bcadd($totalDue, $due, self::MONEY_SCALE);

            $rows[] = [
                'application_id'      => $applicationId,
                'assessment_round_id' => $s->assessment_round_id,
                'period_year'         => $s->year,
                'period_from'         => $s->period_from,
                'period_to'           => $s->period_to,
                'monthly_rent'        => $s->monthly_rent,
                'months_applicable'   => $this->monthsFrom($s),
                'amount_due'          => $due,
                'amount_paid'         => $paid,
                'remission_amount'    => $remitted,
                'balance'             => $balance,
                'note'                => $s->computation_note,
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
        }

        DB::transaction(function () use ($applicationId, $rows) {
            DB::table('arrears_ledger')->where('application_id', $applicationId)->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('arrears_ledger')->insert($chunk);
            }
        });

        $this->refreshApplicationTotals($applicationId);

        return ['rows' => count($rows), 'total_due' => $totalDue];
    }

    /**
     * @return array{total_due: string, total_paid: string, total_remitted: string, balance: string}
     */
    public function summary(int $applicationId): array
    {
        $r = DB::table('arrears_ledger')
            ->where('application_id', $applicationId)
            ->selectRaw('COALESCE(SUM(amount_due),0) AS due, COALESCE(SUM(amount_paid),0) AS paid, '
                . 'COALESCE(SUM(remission_amount),0) AS remitted, COALESCE(SUM(balance),0) AS balance')
            ->first();

        return [
            'total_due'      => bcadd((string) $r->due, '0', self::MONEY_SCALE),
            'total_paid'     => bcadd((string) $r->paid, '0', self::MONEY_SCALE),
            'total_remitted' => bcadd((string) $r->remitted, '0', self::MONEY_SCALE),
            'balance'        => bcadd((string) $r->balance, '0', self::MONEY_SCALE),
        ];
    }

    /**
     * Post a receipt and apply it to the ledger, oldest year first.
     */
    public function postReceipt(
        int $applicationId,
        string $amount,
        string $receiptDate,
        string $mode,
        ?int $userId = null,
        array $extra = [],
    ): string {
        if (bccomp($amount, '0', self::MONEY_SCALE) <= 0) {
            throw new InvalidArgumentException('A receipt amount must be greater than zero.');
        }

        return DB::transaction(function () use ($applicationId, $amount, $receiptDate, $mode, $userId, $extra) {
            $receiptNo = $this->nextReceiptNo($applicationId);

            DB::table('payment_receipts')->insert([
                'application_id' => $applicationId,
                'receipt_no'     => $receiptNo,
                'receipt_date'   => $receiptDate,
                'amount'         => $amount,
                'payment_mode'   => $mode,
                'instrument_no'  => $extra['instrument_no'] ?? null,
                'bank_name'      => $extra['bank_name'] ?? null,
                'branch_code'    => $extra['branch_code'] ?? null,
                'applied_to'     => $extra['applied_to'] ?? 'ARREARS',
                'remarks'        => $extra['remarks'] ?? null,
                'status'         => 'POSTED',
                'received_by'    => $userId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $remaining = $amount;
            $years = DB::table('arrears_ledger')
                ->where('application_id', $applicationId)
                ->where('balance', '>', 0)
                ->orderBy('period_year')
                ->get();

            foreach ($years as $row) {
                if (bccomp($remaining, '0', self::MONEY_SCALE) <= 0) {
                    break;
                }

                $balance = (string) $row->balance;
                $apply = bccomp($remaining, $balance, self::MONEY_SCALE) >= 0 ? $balance : $remaining;

                DB::table('arrears_ledger')->where('id', $row->id)->update([
                    'amount_paid' => bcadd((string) $row->amount_paid, $apply, self::MONEY_SCALE),
                    'balance'     => bcsub($balance, $apply, self::MONEY_SCALE),
                    'updated_at'  => now(),
                ]);

                $remaining = bcsub($remaining, $apply, self::MONEY_SCALE);
            }

            $this->refreshApplicationTotals($applicationId);

            return $receiptNo;
        });
    }

    /**
     * An instalment plan under Clause 13 — not exceeding 24 in number.
     */
    public function proposeInstalmentPlan(
        int $applicationId,
        int $count,
        string $startDate,
        ?string $justification = null,
        ?int $userId = null,
    ): int {
        $max = $this->settings->int('max_instalments', 24);

        if ($count < 1 || $count > $max) {
            throw new InvalidArgumentException(
                "Clause 13 of the Scheme 1977 allows not more than {$max} monthly instalments; {$count} requested."
            );
        }

        $balance = $this->summary($applicationId)['balance'];
        if (bccomp($balance, '0', self::MONEY_SCALE) <= 0) {
            throw new RuntimeException('There is no outstanding balance to spread over instalments.');
        }

        $per = $this->money(bcdiv($balance, (string) $count, 8));
        $start = Carbon::parse($startDate)->startOfDay();

        return DB::transaction(function () use ($applicationId, $count, $per, $balance, $start, $justification, $userId) {
            $planId = DB::table('instalment_plans')->insertGetId([
                'application_id'    => $applicationId,
                'total_amount'      => $balance,
                'instalment_count'  => $count,
                'instalment_amount' => $per,
                'start_date'        => $start->toDateString(),
                'end_date'          => $start->copy()->addMonths($count - 1)->toDateString(),
                'justification'     => $justification,
                'status'            => 'PROPOSED',
                'created_by'        => $userId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $allocated = '0';
            for ($i = 1; $i <= $count; $i++) {
                // The last instalment absorbs the rounding remainder so the
                // schedule sums exactly to the balance.
                $amount = $i === $count
                    ? bcsub($balance, $allocated, self::MONEY_SCALE)
                    : $per;
                $allocated = bcadd($allocated, $amount, self::MONEY_SCALE);

                DB::table('instalment_schedules')->insert([
                    'instalment_plan_id' => $planId,
                    'instalment_no'      => $i,
                    'due_date'           => $start->copy()->addMonths($i - 1)->toDateString(),
                    'amount_due'         => $amount,
                    'status'             => 'PENDING',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            return $planId;
        });
    }

    /**
     * Whether the arrears condition in Clause 3(ii)(b) is satisfied — either
     * the balance is cleared, or an approved instalment plan or Chairman
     * remission is on record.
     *
     * @return array{satisfied: bool, reason: string, balance: string}
     */
    public function clearanceStatus(int $applicationId): array
    {
        $balance = $this->summary($applicationId)['balance'];

        if (bccomp($balance, '0', self::MONEY_SCALE) <= 0) {
            return [
                'satisfied' => true,
                'reason'    => 'All assessed arrears have been cleared.',
                'balance'   => $balance,
            ];
        }

        $plan = DB::table('instalment_plans')
            ->where('application_id', $applicationId)
            ->where('status', 'APPROVED')
            ->whereNull('deleted_at')
            ->first();

        if ($plan) {
            return [
                'satisfied' => true,
                'reason'    => sprintf(
                    'An instalment plan of %d instalments was approved under Clause 13.',
                    $plan->instalment_count
                ),
                'balance'   => $balance,
            ];
        }

        $remission = DB::table('remissions')
            ->where('application_id', $applicationId)
            ->where('status', 'APPROVED')
            ->whereNull('deleted_at')
            ->first();

        if ($remission) {
            return [
                'satisfied' => true,
                'reason'    => 'A remission was approved by the Chairman under Clause 12.',
                'balance'   => $balance,
            ];
        }

        return [
            'satisfied' => false,
            'reason'    => sprintf(
                'Arrears of Rs. %s remain outstanding. Clause 3(ii)(b) requires the occupant to '
                . 'clear all arrears, or an instalment plan (Clause 13) or remission (Clause 12) '
                . 'must be approved, before regularization can be approved.',
                number_format((float) $balance, 2)
            ),
            'balance'   => $balance,
        ];
    }

    private function refreshApplicationTotals(int $applicationId): void
    {
        $s = $this->summary($applicationId);

        DB::table('applications')->where('id', $applicationId)->update([
            'total_arrears'   => $s['total_due'],
            'arrears_paid'    => $s['total_paid'],
            'arrears_balance' => $s['balance'],
            'updated_at'      => now(),
        ]);
    }

    private function monthsFrom(object $schedule): string
    {
        $monthly = (string) $schedule->monthly_rent;

        if (bccomp($monthly, '0', self::MONEY_SCALE) === 0) {
            return '0.0000';
        }

        return bcdiv((string) $schedule->annual_rent, $monthly, 4);
    }

    private function nextReceiptNo(int $applicationId): string
    {
        $count = DB::table('payment_receipts')->where('application_id', $applicationId)->count();

        return sprintf('RCPT/%d/%s/%03d', $applicationId, now()->format('Y'), $count + 1);
    }

    private function money(string $value): string
    {
        $half = '0.' . str_repeat('0', self::MONEY_SCALE) . '5';

        return bcadd(bcadd($value, $half, self::MONEY_SCALE + 1), '0', self::MONEY_SCALE);
    }
}
