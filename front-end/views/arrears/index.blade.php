@extends('layouts.app')

@section('title', 'Arrears')
@section('heading', 'Arrears — ' . $application->application_no)

@section('content')

<div class="page-head">
    <h1>Arrears ledger</h1>
    <p class="lede">
        The occupant must clear all arrears as assessed by the District Officer before being
        treated as a tenant. Where that is not possible, recovery may be spread over up to
        {{ $maxInstalments }} instalments, or rent may be remitted by the Chairman.
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; Case file</a>
        <a href="{{ route('assessment.show', $application) }}" class="badge badge-neutral">Assessment</a>
        <span class="clause">Clause 3(ii)(b)</span>
        <span class="clause">Clause 12</span>
        <span class="clause">Clause 13</span>
    </div>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">Assessed</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_due'], 0) }}</div>
        <div class="tile-sub">
            @if ($application->possession)
                from {{ \Illuminate\Support\Carbon::parse($application->possession->arrears_from)->format('d-m-Y') }}
            @endif
        </div>
    </div>
    <div class="tile">
        <div class="tile-label">Recovered</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_paid'], 0) }}</div>
        <div class="tile-sub">{{ $receipts->count() }} receipt(s)</div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">Remitted</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_remitted'], 0) }}</div>
        <div class="tile-sub">Clause 12</div>
    </div>
    <div class="tile {{ (float) $summary['balance'] > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">Balance</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['balance'], 0) }}</div>
        <div class="tile-sub">{{ $clearance['satisfied'] ? 'Condition satisfied' : 'Blocks approval' }}</div>
    </div>
</div>

<div class="alert {{ $clearance['satisfied'] ? 'alert-good' : 'alert-warn' }}">
    @include('partials.icon', ['name' => $clearance['satisfied'] ? 'check' : 'alert'])
    <div><p class="mb-0">{{ $clearance['reason'] }}</p></div>
</div>

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- Ledger ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Year-by-year ledger</h3>
                <span class="badge badge-neutral">{{ $ledger->count() }} years</span>
                @can('do', 'arrears.generate')
                    <div class="card-actions">
                        <form method="POST" action="{{ route('arrears.regenerate', $application) }}">
                            @csrf
                            <button class="btn btn-outline btn-sm" type="submit">Rebuild from schedule</button>
                        </form>
                    </div>
                @endcan
            </div>

            @if ($ledger->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'cash'])
                    <p class="mb-0">No ledger yet.</p>
                    <p class="hint">The ledger is built when the District Officer fixes the rent.</p>
                    <p class="mt-1">
                        <a href="{{ route('assessment.show', $application) }}" class="btn btn-primary btn-sm">
                            Go to assessment
                        </a>
                    </p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0;max-height:520px;overflow-y:auto">
                    <table class="data">
                        <thead>
                        <tr>
                            <th>Year</th><th class="num">Monthly</th><th class="num">Months</th>
                            <th class="num">Due</th><th class="num">Paid</th>
                            <th class="num">Remitted</th><th class="num">Balance</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($ledger as $row)
                            <tr>
                                <td><strong>{{ $row->period_year }}</strong></td>
                                <td class="num">{{ number_format((float) $row->monthly_rent, 2) }}</td>
                                <td class="num faint">{{ rtrim(rtrim($row->months_applicable, '0'), '.') }}</td>
                                <td class="num">{{ number_format((float) $row->amount_due, 2) }}</td>
                                <td class="num">{{ (float) $row->amount_paid ? number_format((float) $row->amount_paid, 2) : '—' }}</td>
                                <td class="num">{{ (float) $row->remission_amount ? number_format((float) $row->remission_amount, 2) : '—' }}</td>
                                <td class="num">
                                    @if ((float) $row->balance > 0)
                                        <strong>{{ number_format((float) $row->balance, 2) }}</strong>
                                    @else
                                        <span class="badge badge-good">Clear</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr style="background:var(--pk-green-50);font-weight:650">
                            <td colspan="3">Total</td>
                            <td class="num">{{ number_format((float) $summary['total_due'], 2) }}</td>
                            <td class="num">{{ number_format((float) $summary['total_paid'], 2) }}</td>
                            <td class="num">{{ number_format((float) $summary['total_remitted'], 2) }}</td>
                            <td class="num">{{ number_format((float) $summary['balance'], 2) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        {{-- ---------- Receipts ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Receipts</h3>
                <span class="badge badge-neutral">{{ $receipts->count() }}</span>
            </div>

            @if ($receipts->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'empty'])
                    <p class="mb-0">No payments posted.</p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead>
                        <tr><th>Receipt</th><th>Date</th><th>Mode</th><th class="num">Amount</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($receipts as $r)
                            <tr>
                                <td style="font-size:.8rem">{{ $r->receipt_no }}</td>
                                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($r->receipt_date)->format('d-m-Y') }}</td>
                                <td class="faint" style="font-size:.8rem">
                                    {{ ucwords(strtolower(str_replace('_', ' ', $r->payment_mode))) }}
                                    @if ($r->instrument_no)<div>{{ $r->instrument_no }}</div>@endif
                                </td>
                                <td class="num">Rs. {{ number_format((float) $r->amount, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @can('do', 'arrears.receipt')
                @if ($ledger->isNotEmpty())
                    <div class="card-foot">
                        <form method="POST" action="{{ route('arrears.receipts.store', $application) }}">
                            @csrf
                            <p class="hint" style="margin-top:0">Applied to the ledger oldest year first.</p>
                            <div class="grid-3">
                                <div class="field">
                                    <label for="amount">Amount (Rs.)</label>
                                    <input type="text" id="amount" name="amount" class="input" inputmode="decimal" required>
                                </div>
                                <div class="field">
                                    <label for="receipt_date">Date</label>
                                    <input type="date" id="receipt_date" name="receipt_date" class="input"
                                           value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="field">
                                    <label for="payment_mode">Mode</label>
                                    <select id="payment_mode" name="payment_mode" class="select">
                                        @foreach ([
                                            'CASH' => 'Cash', 'PAY_ORDER' => 'Pay order',
                                            'BANKERS_CHEQUE' => "Banker's cheque", 'DEMAND_DRAFT' => 'Demand draft',
                                            'BANK_TRANSFER' => 'Bank transfer', 'CHALLAN' => 'Challan',
                                        ] as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="instrument_no">Instrument no.</label>
                                    <input type="text" id="instrument_no" name="instrument_no" class="input">
                                </div>
                                <div class="field">
                                    <label for="bank_name">Bank</label>
                                    <input type="text" id="bank_name" name="bank_name" class="input">
                                </div>
                                <div class="field">
                                    <label for="branch_code">Branch code</label>
                                    <input type="text" id="branch_code" name="branch_code" class="input">
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Post receipt</button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>

    <div>
        {{-- ---------- Instalments ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Instalment plan</h3>
                <div class="card-actions"><span class="clause">Clause 13</span></div>
            </div>
            <div class="card-body">
                @if ($plan)
                    <dl class="kv">
                        <dt>Total</dt><dd>Rs. {{ number_format((float) $plan->total_amount, 2) }}</dd>
                        <dt>Instalments</dt><dd>{{ $plan->instalment_count }} &times; Rs. {{ number_format((float) $plan->instalment_amount, 2) }}</dd>
                        <dt>Period</dt>
                        <dd>
                            {{ \Illuminate\Support\Carbon::parse($plan->start_date)->format('d-m-Y') }}
                            &ndash;
                            {{ \Illuminate\Support\Carbon::parse($plan->end_date)->format('d-m-Y') }}
                        </dd>
                        <dt>Status</dt>
                        <dd><span class="badge badge-{{ $plan->status === 'APPROVED' ? 'good' : 'warn' }}">{{ ucfirst(strtolower($plan->status)) }}</span></dd>
                    </dl>

                    @if ($plan->justification)
                        <p class="muted" style="font-size:.84rem;white-space:pre-wrap">{{ $plan->justification }}</p>
                    @endif

                    @if ($plan->status === 'PROPOSED')
                        @can('do', 'arrears.instalments')
                            <form method="POST" action="{{ route('instalments.approve', $plan) }}" class="mt-2">
                                @csrf
                                <div class="field">
                                    <label for="plan_reasons">Reasons for allowing instalments <span class="req">*</span></label>
                                    <textarea id="plan_reasons" name="approval_reasons" class="textarea"
                                              style="min-height:70px" required minlength="20"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm" type="submit">Approve plan</button>
                            </form>
                        @endcan
                    @endif

                    @if ($instalments->isNotEmpty())
                        <div class="table-wrap mt-2" style="max-height:240px;overflow-y:auto">
                            <table class="data">
                                <thead><tr><th>#</th><th>Due</th><th class="num">Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                @foreach ($instalments as $i)
                                    <tr>
                                        <td>{{ $i->instalment_no }}</td>
                                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($i->due_date)->format('d-m-Y') }}</td>
                                        <td class="num">{{ number_format((float) $i->amount_due, 2) }}</td>
                                        <td><span class="badge badge-{{ $i->status === 'PAID' ? 'good' : 'neutral' }}">{{ ucfirst(strtolower($i->status)) }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @elseif ((float) $summary['balance'] > 0)
                    @can('do', 'arrears.instalments')
                        <p class="muted" style="font-size:.86rem">
                            In deserving cases the District Officer may allow recovery in reasonable
                            monthly instalments, not exceeding {{ $maxInstalments }} in number.
                        </p>
                        <form method="POST" action="{{ route('arrears.instalments.store', $application) }}">
                            @csrf
                            <div class="grid-2">
                                <div class="field">
                                    <label for="instalment_count">Instalments</label>
                                    <input type="number" id="instalment_count" name="instalment_count" class="input"
                                           min="1" max="{{ $maxInstalments }}" value="24" required>
                                </div>
                                <div class="field">
                                    <label for="start_date">First due</label>
                                    <input type="date" id="start_date" name="start_date" class="input"
                                           value="{{ now()->addMonth()->startOfMonth()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="justification">What makes this case deserving <span class="req">*</span></label>
                                <textarea id="justification" name="justification" class="textarea"
                                          style="min-height:80px" required minlength="20"></textarea>
                            </div>
                            <button class="btn btn-outline btn-sm" type="submit">Propose plan</button>
                        </form>
                    @endcan
                @else
                    <p class="muted mb-0">Nothing outstanding to spread over instalments.</p>
                @endif
            </div>
        </div>

        {{-- ---------- Remission ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Remission</h3>
                <div class="card-actions"><span class="clause">Clause 12</span></div>
            </div>
            <div class="card-body">
                <p class="muted" style="font-size:.86rem">
                    The Chairman may assess a nominal rent, or remit rent or arrears, for persons
                    who are indigent, orphans, widows or otherwise incapable of meeting the liability.
                    Only the Chairman is competent to grant it.
                </p>

                @if ($application->applicant?->hasRemissionGround())
                    <div class="alert alert-info">
                        @include('partials.icon', ['name' => 'info'])
                        <div>
                            <p class="mb-0">
                                The applicant is recorded as
                                @if ($application->applicant->is_indigent) indigent @endif
                                @if ($application->applicant->is_widow) a widow @endif
                                @if ($application->applicant->is_orphan) an orphan @endif
                                — a Clause 12 ground is already on file.
                            </p>
                        </div>
                    </div>
                @endif

                @foreach ($remissions as $rem)
                    <div class="inline-list">
                        <span class="badge badge-{{ $rem->status === 'APPROVED' ? 'good' : ($rem->status === 'REJECTED' ? 'danger' : 'warn') }}">
                            {{ ucfirst(strtolower($rem->status)) }}
                        </span>
                        <span class="badge badge-gold">{{ ucfirst(strtolower($rem->ground)) }}</span>
                        <span class="faint" style="font-size:.8rem">
                            {{ ucwords(strtolower(str_replace('_', ' ', $rem->remission_type))) }}
                        </span>
                    </div>
                    <p class="muted" style="font-size:.84rem;white-space:pre-wrap;margin:.4rem 0">{{ $rem->grounds_detail }}</p>

                    @if ($rem->status === 'PROPOSED')
                        @can('do', 'arrears.remit')
                            <form method="POST" action="{{ route('remissions.approve', $rem) }}">
                                @csrf
                                <div class="field">
                                    <label for="rdec_{{ $rem->id }}">Decision</label>
                                    <select id="rdec_{{ $rem->id }}" name="decision" class="select">
                                        <option value="APPROVED">Approve</option>
                                        <option value="REJECTED">Reject</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="rref_{{ $rem->id }}">Order reference</label>
                                    <input type="text" id="rref_{{ $rem->id }}" name="order_reference" class="input">
                                </div>
                                <div class="field">
                                    <label for="rrea_{{ $rem->id }}">Reasons <span class="req">*</span></label>
                                    <textarea id="rrea_{{ $rem->id }}" name="approval_reasons" class="textarea"
                                              style="min-height:70px" required minlength="20"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm" type="submit">Record decision</button>
                            </form>
                        @else
                            <p class="hint">Awaiting the Chairman.</p>
                        @endcan
                    @elseif ($rem->approval_reasons)
                        <p class="faint" style="font-size:.82rem;white-space:pre-wrap">{{ $rem->approval_reasons }}</p>
                    @endif
                    <hr class="divider">
                @endforeach

                @if ((float) $summary['balance'] > 0 && $remissions->where('status', 'PROPOSED')->isEmpty())
                    <form method="POST" action="{{ route('arrears.remissions.store', $application) }}">
                        @csrf
                        <div class="grid-2">
                            <div class="field">
                                <label for="ground">Ground</label>
                                <select id="ground" name="ground" class="select">
                                    <option value="INDIGENT">Indigent</option>
                                    <option value="WIDOW">Widow</option>
                                    <option value="ORPHAN">Orphan</option>
                                    <option value="INCAPABLE">Otherwise incapable</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="remission_type">Relief sought</label>
                                <select id="remission_type" name="remission_type" class="select">
                                    <option value="REMIT_ARREARS">Remit arrears</option>
                                    <option value="NOMINAL_RENT">Assess a nominal rent</option>
                                    <option value="REMIT_RENT">Remit rent</option>
                                    <option value="PARTIAL">Partial</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="nominal_monthly_rent">Nominal monthly rent (Rs.)</label>
                                <input type="text" id="nominal_monthly_rent" name="nominal_monthly_rent"
                                       class="input" inputmode="decimal">
                            </div>
                            <div class="field">
                                <label for="remitted_percentage">Partial (%)</label>
                                <input type="text" id="remitted_percentage" name="remitted_percentage"
                                       class="input" inputmode="decimal">
                            </div>
                        </div>
                        <div class="field">
                            <label for="grounds_detail">Circumstances <span class="req">*</span></label>
                            <textarea id="grounds_detail" name="grounds_detail" class="textarea"
                                      style="min-height:80px" required minlength="30"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">Propose remission</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
