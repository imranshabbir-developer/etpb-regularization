<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\FeePayment;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Head 5 — the Rs. 5,000 deposit.
 *
 * The client's rule, verbatim: an application whose payment has not been made
 * "will not be process[ed]", and its status is "pending"; once the amount is
 * deposited the status becomes "paid" and only then do officers act on it.
 *
 * Two distinct acts are involved and they belong to different people:
 *
 *   1. the applicant (or a dealing assistant) RECORDS the instrument — the pay
 *      order, banker's cheque or demand draft drawn in favour of Chairman ETPB;
 *   2. ACCOUNTS CONFIRMS it against the bank, and only that flips the
 *      application to PAID.
 *
 * Recording alone must never unlock processing, or the fee becomes a
 * self-declaration.
 */
class FeeController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    public function index(Request $request, Application $application): View
    {
        $this->authoriseView($request, $application);

        return view('fee.index', [
            'application' => $application->load(['applicant', 'property', 'district']),
            'payments'    => $application->feePayments()->orderByDesc('id')->get(),
            'feeAmount'   => $this->settings->decimal('processing_fee', '5000.00'),
            'payee'       => 'Chairman ETPB',
            'districts'   => DB::table('districts')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Record the instrument. Every field here is one the client listed under
     * Head 5: instrument, date of submission, amount, bank with branch location
     * and code, district, and the depositor's name, CNIC and contact.
     */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authoriseView($request, $application);

        $expected = $this->settings->decimal('processing_fee', '5000.00');

        $data = $request->validate([
            'instrument_type'   => ['required', Rule::in(['PAY_ORDER', 'BANKERS_CHEQUE', 'DEMAND_DRAFT'])],
            'instrument_no'     => ['required', 'string', 'max:60'],
            'instrument_date'   => ['required', 'date', 'before_or_equal:today'],
            'amount'            => ['required', 'numeric', 'min:0'],
            'bank_name'         => ['required', 'string', 'max:150'],
            'branch_name'       => ['required', 'string', 'max:150'],
            'branch_code'       => ['nullable', 'string', 'max:30'],
            'district_id'       => ['nullable', 'integer', 'exists:districts,id'],
            'depositor_name'    => ['required', 'string', 'max:150'],
            'depositor_cnic'    => ['required', 'digits:13'],
            'depositor_contact' => ['required', 'string', 'max:20'],
            'submission_date'   => ['required', 'date', 'before_or_equal:today'],
        ], [
            'depositor_cnic.digits' => 'The depositor CNIC must be exactly 13 digits, without dashes.',
        ]);

        // A short deposit does not satisfy the condition, so it is refused at
        // entry rather than left for Accounts to discover.
        if (bccomp((string) $data['amount'], $expected, 2) < 0) {
            return back()->withInput()->with('error', sprintf(
                'The deposit must be at least Rs. %s. Rs. %s was entered.',
                number_format((float) $expected, 2),
                number_format((float) $data['amount'], 2),
            ));
        }

        FeePayment::create($data + [
            'application_id' => $application->id,
            'payee'          => 'Chairman ETPB',
            'status'         => 'PENDING',
            'created_by'     => $request->user()->id,
        ]);

        return back()->with('status',
            'Deposit recorded. The application stays PENDING until Accounts confirms the instrument with the bank.');
    }

    /**
     * Accounts confirms — or rejects — the instrument. This is the only place
     * an application becomes PAID.
     */
    public function confirm(Request $request, FeePayment $payment): RedirectResponse
    {
        $application = $payment->application;
        $this->authoriseView($request, $application);

        $data = $request->validate([
            'decision'              => ['required', Rule::in(['VERIFIED', 'BOUNCED', 'REJECTED'])],
            'bank_confirmation_ref' => ['nullable', 'string', 'max:100'],
            'verification_remarks'  => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'verification_remarks.required' => 'Record how the instrument was confirmed, or why it was refused.',
        ]);

        DB::transaction(function () use ($payment, $application, $data, $request) {
            $payment->update([
                'status'                => $data['decision'],
                'bank_confirmation_ref' => $data['bank_confirmation_ref'] ?? null,
                'verification_remarks'  => $data['verification_remarks'],
                'verified_by'           => $request->user()->id,
                'verified_at'           => now(),
            ]);

            $verified = $application->feePayments()
                ->where('status', 'VERIFIED')
                ->sum('amount');

            $expected = $this->settings->decimal('processing_fee', '5000.00');
            $paid = bccomp((string) $verified, $expected, 2) >= 0;

            $application->forceFill([
                'payment_status'       => $paid ? 'PAID' : 'PENDING',
                'payment_confirmed_at' => $paid ? now() : null,
                'payment_confirmed_by' => $paid ? $request->user()->id : null,
            ])->save();

            DB::table('application_status_history')->insert([
                'application_id' => $application->id,
                'from_status'    => $application->status,
                'to_status'      => $application->status,
                'action'         => $paid ? 'PAYMENT_CONFIRMED' : 'PAYMENT_' . $data['decision'],
                'remarks'        => sprintf(
                    'Instrument %s (%s) marked %s. %s',
                    $payment->instrument_no,
                    $payment->bank_name,
                    $data['decision'],
                    $data['verification_remarks'],
                ),
                'actor_id'    => $request->user()->id,
                'actor_role'  => $request->user()->primaryRole()?->code,
                'ip_address'  => $request->ip(),
                'occurred_at' => now(),
            ]);
        });

        $application->refresh();

        return back()->with('status', $application->payment_status === 'PAID'
            ? 'Payment confirmed. The application is now marked PAID and may be processed.'
            : 'Instrument marked ' . strtolower($data['decision']) . '. The application remains PENDING.');
    }

    private function authoriseView(Request $request, Application $application): void
    {
        $user = $request->user();

        if ($user->hasPermission('applications.view_all')) {
            return;
        }
        if ($user->hasPermission('applications.view_district')
            && (int) $application->district_id === (int) $user->district_id) {
            return;
        }
        if ($application->applicant?->user_id === $user->id) {
            return;
        }

        abort(403, 'This application is outside your jurisdiction.');
    }
}
