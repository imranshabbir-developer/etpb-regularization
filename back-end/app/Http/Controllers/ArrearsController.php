<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\InstalmentPlan;
use App\Models\Remission;
use App\Services\ArrearsService;
use App\Services\SettingService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Arrears, and the three ways Clause 3(ii)(b) can be satisfied.
 *
 * The occupant must clear all arrears as assessed by the District Officer
 * before being treated as a tenant. Where that is not possible, Clause 13
 * allows recovery in not more than twenty four monthly instalments, and
 * Clause 12 allows the Chairman to assess a nominal rent or remit rent or
 * arrears altogether for persons who are indigent, orphans, widows or
 * otherwise incapable of meeting the liability.
 *
 * Retrospective assessment back to 2000 can produce a figure the occupant
 * cannot pay in one sum, so these are first-class features rather than
 * exceptions bolted on later.
 */
class ArrearsController extends Controller
{
    public function __construct(
        private readonly ArrearsService $arrears,
        private readonly SettingService $settings,
        private readonly WorkflowService $workflow,
    ) {
    }

    public function index(Request $request, Application $application): View
    {
        $this->authoriseDistrict($request, $application);

        $ledger = DB::table('arrears_ledger')
            ->where('application_id', $application->id)
            ->orderBy('period_year')
            ->get();

        $plan = InstalmentPlan::where('application_id', $application->id)
            ->whereIn('status', ['PROPOSED', 'APPROVED'])
            ->latest('id')
            ->first();

        return view('arrears.index', [
            'application'  => $application->load(['applicant', 'property', 'possession']),
            'ledger'       => $ledger,
            'summary'      => $this->arrears->summary($application->id),
            'clearance'    => $this->arrears->clearanceStatus($application->id),
            'receipts'     => $application->receipts()->orderByDesc('receipt_date')->get(),
            'plan'         => $plan,
            'instalments'  => $plan
                ? DB::table('instalment_schedules')->where('instalment_plan_id', $plan->id)
                    ->orderBy('instalment_no')->get()
                : collect(),
            'remissions'   => Remission::where('application_id', $application->id)->latest('id')->get(),
            'maxInstalments' => $this->settings->int('max_instalments', 24),
        ]);
    }

    /**
     * Rebuild the ledger from the current rent schedule, preserving payments
     * and remissions already posted against each year.
     */
    public function regenerate(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        try {
            $result = $this->arrears->generate($application->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', sprintf(
            'Ledger rebuilt: %d year(s), Rs. %s assessed.',
            $result['rows'],
            number_format((float) $result['total_due'], 2),
        ));
    }

    public function storeReceipt(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'receipt_date'  => ['required', 'date', 'before_or_equal:today'],
            'payment_mode'  => ['required', Rule::in([
                'CASH', 'PAY_ORDER', 'BANKERS_CHEQUE', 'DEMAND_DRAFT', 'BANK_TRANSFER', 'CHALLAN',
            ])],
            'instrument_no' => ['nullable', 'string', 'max:60'],
            'bank_name'     => ['nullable', 'string', 'max:150'],
            'branch_code'   => ['nullable', 'string', 'max:30'],
            'remarks'       => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $receiptNo = $this->arrears->postReceipt(
                $application->id,
                (string) $data['amount'],
                $data['receipt_date'],
                $data['payment_mode'],
                $request->user()->id,
                $data,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $balance = $this->arrears->summary($application->id)['balance'];

        return back()->with('status', sprintf(
            'Receipt %s posted for Rs. %s. Balance now Rs. %s.',
            $receiptNo,
            number_format((float) $data['amount'], 2),
            number_format((float) $balance, 2),
        ));
    }

    public function proposeInstalments(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $max = $this->settings->int('max_instalments', 24);

        $data = $request->validate([
            'instalment_count' => ['required', 'integer', 'min:1', "max:{$max}"],
            'start_date'       => ['required', 'date'],
            'justification'    => ['required', 'string', 'min:20', 'max:4000'],
        ], [
            'instalment_count.max' => "Clause 13 of the Scheme 1977 allows not more than {$max} monthly instalments.",
            'justification.required' => 'Clause 13 applies in deserving cases. Record what makes this one deserving.',
        ]);

        try {
            $this->arrears->proposeInstalmentPlan(
                $application->id,
                (int) $data['instalment_count'],
                $data['start_date'],
                $data['justification'],
                $request->user()->id,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('status', sprintf(
            'Instalment plan of %d instalments proposed.',
            $data['instalment_count'],
        ));
    }

    public function approveInstalments(Request $request, InstalmentPlan $plan): RedirectResponse
    {
        $application = $plan->application;
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'approval_reasons' => ['required', 'string', 'min:20', 'max:4000'],
        ], [
            'approval_reasons.required' => 'Record the reasons for allowing recovery by instalments.',
        ]);

        $plan->update([
            'status'           => 'APPROVED',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'approval_reasons' => $data['approval_reasons'],
        ]);

        return back()->with('status', 'Instalment plan approved under Clause 13.');
    }

    /**
     * Propose a remission. Only the Chairman is competent to grant one, so the
     * District Officer proposes and the approval is a separate, senior act.
     */
    public function proposeRemission(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseDistrict($request, $application);

        $data = $request->validate([
            'ground'                => ['required', Rule::in(['INDIGENT', 'ORPHAN', 'WIDOW', 'INCAPABLE', 'OTHER'])],
            'remission_type'        => ['required', Rule::in(['NOMINAL_RENT', 'REMIT_RENT', 'REMIT_ARREARS', 'PARTIAL'])],
            'nominal_monthly_rent'  => ['nullable', 'numeric', 'min:0', 'required_if:remission_type,NOMINAL_RENT'],
            'remitted_amount'       => ['nullable', 'numeric', 'min:0'],
            'remitted_percentage'   => ['nullable', 'numeric', 'between:0,100'],
            'grounds_detail'        => ['required', 'string', 'min:30', 'max:4000'],
            'supporting_evidence'   => ['nullable', 'string', 'max:4000'],
        ], [
            'grounds_detail.required' => 'Clause 12 turns on the applicant\'s circumstances. Set them out.',
        ]);

        Remission::create($data + [
            'application_id' => $application->id,
            'status'         => 'PROPOSED',
            'created_by'     => $request->user()->id,
        ]);

        return back()->with('status',
            'Remission proposed under Clause 12. Only the Chairman is competent to grant it.');
    }

    public function approveRemission(Request $request, Remission $remission): RedirectResponse
    {
        $application = $remission->application;

        $data = $request->validate([
            'approval_reasons' => ['required', 'string', 'min:20', 'max:4000'],
            'order_reference'  => ['nullable', 'string', 'max:120'],
            'decision'         => ['required', Rule::in(['APPROVED', 'REJECTED'])],
        ], [
            'approval_reasons.required' => 'Record the reasons for the decision on remission.',
        ]);

        DB::transaction(function () use ($remission, $data, $request, $application) {
            $remission->update([
                'status'           => $data['decision'],
                'approved_by'      => $request->user()->id,
                'approved_at'      => now(),
                'approval_reasons' => $data['approval_reasons'],
                'order_reference'  => $data['order_reference'] ?? null,
            ]);

            if ($data['decision'] !== 'APPROVED') {
                return;
            }

            // Apply the remission across the ledger, oldest year first.
            $remaining = $this->remittedAmount($remission, $application->id);

            $rows = DB::table('arrears_ledger')
                ->where('application_id', $application->id)
                ->where('balance', '>', 0)
                ->orderBy('period_year')
                ->get();

            foreach ($rows as $row) {
                if (bccomp($remaining, '0', 2) <= 0) {
                    break;
                }
                $balance = (string) $row->balance;
                $apply = bccomp($remaining, $balance, 2) >= 0 ? $balance : $remaining;

                DB::table('arrears_ledger')->where('id', $row->id)->update([
                    'remission_amount' => bcadd((string) $row->remission_amount, $apply, 2),
                    'balance'          => bcsub($balance, $apply, 2),
                    'updated_at'       => now(),
                ]);

                $remaining = bcsub($remaining, $apply, 2);
            }
        });

        // Refresh the rolled-up totals on the application.
        $this->arrears->generate($application->id);

        return back()->with('status', $data['decision'] === 'APPROVED'
            ? 'Remission approved under Clause 12 and applied to the ledger.'
            : 'Remission rejected.');
    }

    /** How much of the outstanding balance a remission actually wipes. */
    private function remittedAmount(Remission $remission, int $applicationId): string
    {
        $balance = $this->arrears->summary($applicationId)['balance'];

        return match ($remission->remission_type) {
            'REMIT_ARREARS', 'REMIT_RENT' => $balance,
            'PARTIAL' => $remission->remitted_percentage !== null
                ? bcdiv(bcmul($balance, (string) $remission->remitted_percentage, 4), '100', 2)
                : (string) ($remission->remitted_amount ?? '0'),
            default => (string) ($remission->remitted_amount ?? '0'),
        };
    }

    private function authoriseDistrict(Request $request, Application $application): void
    {
        $user = $request->user();

        if ($user->hasPermission('applications.view_all')) {
            return;
        }
        if ($user->hasPermission('applications.view_district')
            && (int) $application->district_id === (int) $user->district_id) {
            return;
        }
        // An applicant may see their own ledger. They have to: Clause 3(ii)(b)
        // requires them to clear the arrears, and they cannot pay a figure they
        // are never shown. Posting receipts and approving instalments remain
        // permission-gated, so this is read access to their own liability.
        if ($application->applicant?->user_id === $user->id) {
            return;
        }

        abort(403, 'This application is outside your jurisdiction.');
    }
}
