@extends('layouts.app')

@section('title', __('Arrears'))
@section('heading', __('Arrears — :no', ['no' => $application->application_no]))

@section('content')

<div class="page-head">
    <h1>{{ __('Arrears ledger') }}</h1>
    <p class="lede">
        {{ __('The occupant must clear all arrears as assessed by the District Officer before being treated as a tenant. Where that is not possible, recovery may be spread over up to :max instalments, or rent may be remitted by the Chairman.', ['max' => $maxInstalments]) }}
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; {{ __('Case file') }}</a>
        <a href="{{ route('assessment.show', $application) }}" class="badge badge-neutral">{{ __('Assessment') }}</a>
        <span class="clause">{{ __('Clause :no', ['no' => '3(ii)(b)']) }}</span>
        <span class="clause">{{ __('Clause :no', ['no' => '12']) }}</span>
        <span class="clause">{{ __('Clause :no', ['no' => '13']) }}</span>
    </div>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('Assessed') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_due'], 0) }}</div>
        <div class="tile-sub">
            @if ($application->possession)
                {{ __('from :date', ['date' => \Illuminate\Support\Carbon::parse($application->possession->arrears_from)->translatedFormat('d-m-Y')]) }}
            @endif
        </div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Recovered') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_paid'], 0) }}</div>
        <div class="tile-sub">{{ __(':count receipt(s)', ['count' => $receipts->count()]) }}</div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">{{ __('Remitted') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['total_remitted'], 0) }}</div>
        <div class="tile-sub">{{ __('Clause :no', ['no' => '12']) }}</div>
    </div>
    <div class="tile {{ (float) $summary['balance'] > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">{{ __('Balance') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $summary['balance'], 0) }}</div>
        <div class="tile-sub">{{ $clearance['satisfied'] ? __('Condition satisfied') : __('Blocks approval') }}</div>
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
                <h3>{{ __('Year-by-year ledger') }}</h3>
                <span class="badge badge-neutral">{{ __(':count years', ['count' => $ledger->count()]) }}</span>
                @can('do', 'arrears.generate')
                    <div class="card-actions">
                        <form method="POST" action="{{ route('arrears.regenerate', $application) }}">
                            @csrf
                            <button class="btn btn-outline btn-sm" type="submit">{{ __('Rebuild from schedule') }}</button>
                        </form>
                    </div>
                @endcan
            </div>

            @if ($ledger->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'cash'])
                    <p class="mb-0">{{ __('No ledger yet.') }}</p>
                    <p class="hint">{{ __('The ledger is built when the District Officer fixes the rent.') }}</p>
                    <p class="mt-1">
                        <a href="{{ route('assessment.show', $application) }}" class="btn btn-primary btn-sm">
                            {{ __('Go to assessment') }}
                        </a>
                    </p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0;max-height:520px;overflow-y:auto">
                    <table class="data">
                        <thead>
                        <tr>
                            <th>{{ __('Year') }}</th><th class="num">{{ __('Monthly') }}</th><th class="num">{{ __('Months') }}</th>
                            <th class="num">{{ __('Due') }}</th><th class="num">{{ __('Paid') }}</th>
                            <th class="num">{{ __('Remitted') }}</th><th class="num">{{ __('Balance') }}</th>
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
                                        <span class="badge badge-good">{{ __('Clear') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr style="background:var(--pk-green-50);font-weight:650">
                            <td colspan="3">{{ __('Total') }}</td>
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
                <h3>{{ __('Receipts') }}</h3>
                <span class="badge badge-neutral">{{ $receipts->count() }}</span>
            </div>

            @if ($receipts->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'empty'])
                    <p class="mb-0">{{ __('No payments posted.') }}</p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead>
                        <tr><th>{{ __('Receipt') }}</th><th>{{ __('Date') }}</th><th>{{ __('Mode') }}</th><th class="num">{{ __('Amount') }}</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($receipts as $r)
                            <tr>
                                <td style="font-size:.8rem">{{ $r->receipt_no }}</td>
                                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($r->receipt_date)->translatedFormat('d-m-Y') }}</td>
                                <td class="faint" style="font-size:.8rem">
                                    {{ __(ucwords(strtolower(str_replace('_', ' ', $r->payment_mode)))) }}
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
                            <p class="hint" style="margin-top:0">{{ __('Applied to the ledger oldest year first.') }}</p>
                            <div class="grid-3">
                                <div class="field">
                                    <label for="amount">{{ __('Amount (Rs.)') }}</label>
                                    <input type="text" id="amount" name="amount" class="input" inputmode="decimal" required>
                                </div>
                                <div class="field">
                                    <label for="receipt_date">{{ __('Date') }}</label>
                                    <input type="date" id="receipt_date" name="receipt_date" class="input"
                                           value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="field">
                                    <label for="payment_mode">{{ __('Mode') }}</label>
                                    <select id="payment_mode" name="payment_mode" class="select">
                                        @foreach ([
                                            'CASH' => 'Cash', 'PAY_ORDER' => 'Pay order',
                                            'BANKERS_CHEQUE' => "Banker's cheque", 'DEMAND_DRAFT' => 'Demand draft',
                                            'BANK_TRANSFER' => 'Bank transfer', 'CHALLAN' => 'Challan',
                                        ] as $v => $l)
                                            <option value="{{ $v }}">{{ __($l) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="instrument_no">{{ __('Instrument no.') }}</label>
                                    <input type="text" id="instrument_no" name="instrument_no" class="input">
                                </div>
                                <div class="field">
                                    <label for="bank_name">{{ __('Bank') }}</label>
                                    <input type="text" id="bank_name" name="bank_name" class="input">
                                </div>
                                <div class="field">
                                    <label for="branch_code">{{ __('Branch code') }}</label>
                                    <input type="text" id="branch_code" name="branch_code" class="input">
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">{{ __('Post receipt') }}</button>
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
                <h3>{{ __('Instalment plan') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Clause :no', ['no' => '13']) }}</span></div>
            </div>
            <div class="card-body">
                @if ($plan)
                    <dl class="kv">
                        <dt>{{ __('Total') }}</dt><dd>Rs. {{ number_format((float) $plan->total_amount, 2) }}</dd>
                        <dt>{{ __('Instalments') }}</dt><dd>{{ __(':count × Rs. :amount', ['count' => $plan->instalment_count, 'amount' => number_format((float) $plan->instalment_amount, 2)]) }}</dd>
                        <dt>{{ __('Period') }}</dt>
                        <dd>
                            {{ \Illuminate\Support\Carbon::parse($plan->start_date)->translatedFormat('d-m-Y') }}
                            &ndash;
                            {{ \Illuminate\Support\Carbon::parse($plan->end_date)->translatedFormat('d-m-Y') }}
                        </dd>
                        <dt>{{ __('Status') }}</dt>
                        <dd><span class="badge badge-{{ $plan->status === 'APPROVED' ? 'good' : 'warn' }}">{{ __(ucfirst(strtolower($plan->status))) }}</span></dd>
                    </dl>

                    @if ($plan->justification)
                        <p class="muted" style="font-size:.84rem;white-space:pre-wrap">{{ $plan->justification }}</p>
                    @endif

                    @if ($plan->status === 'PROPOSED')
                        @can('do', 'arrears.instalments')
                            <form method="POST" action="{{ route('instalments.approve', $plan) }}" class="mt-2">
                                @csrf
                                <div class="field">
                                    <label for="plan_reasons">{{ __('Reasons for allowing instalments') }} <span class="req">*</span></label>
                                    <textarea id="plan_reasons" name="approval_reasons" class="textarea"
                                              style="min-height:70px" required minlength="20"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm" type="submit">{{ __('Approve plan') }}</button>
                            </form>
                        @endcan
                    @endif

                    @if ($instalments->isNotEmpty())
                        <div class="table-wrap mt-2" style="max-height:240px;overflow-y:auto">
                            <table class="data">
                                <thead><tr><th>#</th><th>{{ __('Due') }}</th><th class="num">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                                <tbody>
                                @foreach ($instalments as $i)
                                    <tr>
                                        <td>{{ $i->instalment_no }}</td>
                                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($i->due_date)->translatedFormat('d-m-Y') }}</td>
                                        <td class="num">{{ number_format((float) $i->amount_due, 2) }}</td>
                                        <td><span class="badge badge-{{ $i->status === 'PAID' ? 'good' : 'neutral' }}">{{ __(ucfirst(strtolower($i->status))) }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @elseif ((float) $summary['balance'] > 0)
                    @can('do', 'arrears.instalments')
                        <p class="muted" style="font-size:.86rem">
                            {{ __('In deserving cases the District Officer may allow recovery in reasonable monthly instalments, not exceeding :max in number.', ['max' => $maxInstalments]) }}
                        </p>
                        <form method="POST" action="{{ route('arrears.instalments.store', $application) }}">
                            @csrf
                            <div class="grid-2">
                                <div class="field">
                                    <label for="instalment_count">{{ __('Instalments') }}</label>
                                    <input type="number" id="instalment_count" name="instalment_count" class="input"
                                           min="1" max="{{ $maxInstalments }}" value="24" required>
                                </div>
                                <div class="field">
                                    <label for="start_date">{{ __('First due') }}</label>
                                    <input type="date" id="start_date" name="start_date" class="input"
                                           value="{{ now()->addMonth()->startOfMonth()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="justification">{{ __('What makes this case deserving') }} <span class="req">*</span></label>
                                <textarea id="justification" name="justification" class="textarea"
                                          style="min-height:80px" required minlength="20"></textarea>
                            </div>
                            <button class="btn btn-outline btn-sm" type="submit">{{ __('Propose plan') }}</button>
                        </form>
                    @endcan
                @else
                    <p class="muted mb-0">{{ __('Nothing outstanding to spread over instalments.') }}</p>
                @endif
            </div>
        </div>

        {{-- ---------- Remission ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Remission') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Clause :no', ['no' => '12']) }}</span></div>
            </div>
            <div class="card-body">
                <p class="muted" style="font-size:.86rem">
                    {{ __('The Chairman may assess a nominal rent, or remit rent or arrears, where the applicant is otherwise incapable of meeting the liability or on other recorded grounds. Only the Chairman is competent to grant it.') }}
                </p>

                @foreach ($remissions as $rem)
                    <div class="inline-list">
                        <span class="badge badge-{{ $rem->status === 'APPROVED' ? 'good' : ($rem->status === 'REJECTED' ? 'danger' : 'warn') }}">
                            {{ __(ucfirst(strtolower($rem->status))) }}
                        </span>
                        <span class="badge badge-gold">{{ __(ucfirst(strtolower($rem->ground))) }}</span>
                        <span class="faint" style="font-size:.8rem">
                            {{ __(ucwords(strtolower(str_replace('_', ' ', $rem->remission_type)))) }}
                        </span>
                    </div>
                    <p class="muted" style="font-size:.84rem;white-space:pre-wrap;margin:.4rem 0">{{ $rem->grounds_detail }}</p>

                    @if ($rem->status === 'PROPOSED')
                        @can('do', 'arrears.remit')
                            <form method="POST" action="{{ route('remissions.approve', $rem) }}">
                                @csrf
                                <div class="field">
                                    <label for="rdec_{{ $rem->id }}">{{ __('Decision') }}</label>
                                    <select id="rdec_{{ $rem->id }}" name="decision" class="select">
                                        <option value="APPROVED">{{ __('Approve') }}</option>
                                        <option value="REJECTED">{{ __('Reject') }}</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="rref_{{ $rem->id }}">{{ __('Order reference') }}</label>
                                    <input type="text" id="rref_{{ $rem->id }}" name="order_reference" class="input">
                                </div>
                                <div class="field">
                                    <label for="rrea_{{ $rem->id }}">{{ __('Reasons') }} <span class="req">*</span></label>
                                    <textarea id="rrea_{{ $rem->id }}" name="approval_reasons" class="textarea"
                                              style="min-height:70px" required minlength="20"></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm" type="submit">{{ __('Record decision') }}</button>
                            </form>
                        @else
                            <p class="hint">{{ __('Awaiting the Chairman.') }}</p>
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
                                <label for="ground">{{ __('Ground') }}</label>
                                <select id="ground" name="ground" class="select">
                                    <option value="INCAPABLE">{{ __('Otherwise incapable') }}</option>
                                    <option value="OTHER">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="remission_type">{{ __('Relief sought') }}</label>
                                <select id="remission_type" name="remission_type" class="select">
                                    <option value="REMIT_ARREARS">{{ __('Remit arrears') }}</option>
                                    <option value="NOMINAL_RENT">{{ __('Assess a nominal rent') }}</option>
                                    <option value="REMIT_RENT">{{ __('Remit rent') }}</option>
                                    <option value="PARTIAL">{{ __('Partial') }}</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="nominal_monthly_rent">{{ __('Nominal monthly rent (Rs.)') }}</label>
                                <input type="text" id="nominal_monthly_rent" name="nominal_monthly_rent"
                                       class="input" inputmode="decimal">
                            </div>
                            <div class="field">
                                <label for="remitted_percentage">{{ __('Partial (%)') }}</label>
                                <input type="text" id="remitted_percentage" name="remitted_percentage"
                                       class="input" inputmode="decimal">
                            </div>
                        </div>
                        <div class="field">
                            <label for="grounds_detail">{{ __('Circumstances') }} <span class="req">*</span></label>
                            <textarea id="grounds_detail" name="grounds_detail" class="textarea"
                                      style="min-height:80px" required minlength="30"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">{{ __('Propose remission') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
