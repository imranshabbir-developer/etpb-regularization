@extends('layouts.app')

@section('title', __('Approval'))
@section('heading', __('Approval — :no', ['no' => $application->application_no]))

@section('content')

@php
    $a = $application;
    $p = $a->property;
    $poss = $a->possession;
    $fmt = fn ($d, $f = 'd-m-Y') => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat($f) : '—';
    $rs  = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
    $decided = $a->approvals->where('level', 'ADMINISTRATOR')->isNotEmpty();
@endphp

<div class="page-head">
    <h1>{{ __('Approval of regularization') }}</h1>
    <p class="lede">
        {{ $a->applicant?->nameWithParentage(true) }} &mdash; {{ $p?->identity(true) }},
        {{ __($p?->district?->name ?? '') }}
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('queue.approvals') }}" class="badge badge-neutral">&larr; {{ __('Approval queue') }}</a>
        <a href="{{ route('applications.show', $a) }}" class="badge badge-neutral">{{ __('Case file') }}</a>
        <a href="{{ route('reports.deep', $a) }}" class="badge badge-neutral">{{ __('Deep report') }}</a>
        <span class="badge badge-{{ $a->statusTone() }}">{{ __($a->statusLabel()) }}</span>
        <span class="clause">{{ __('Clause 3(ii)(d)') }}</span>
    </div>
</div>

{{-- ---------- The one-month clock ---------- --}}
@if ($sla['applies'])
    <div class="tiles">
        <div class="tile {{ $sla['tone'] === 'danger' ? 'is-danger' : ($sla['tone'] === 'warn' ? 'is-warn' : '') }}">
            <div class="tile-label">{{ __('One month to approve') }} <span class="clause">{{ __('Cl. 3(ii)(d)') }}</span></div>
            <div class="tile-value" style="font-size:1.15rem">
                {{ $sla['days_left'] < 0
                    ? __('Approval overdue by :days day(s)', ['days' => abs($sla['days_left'])])
                    : __('Approval due in :days day(s)', ['days' => $sla['days_left']]) }}
            </div>
            <div class="sla mt-1">
                <div class="sla-bar">
                    <div class="sla-fill {{ $sla['tone'] === 'danger' ? 'is-danger' : ($sla['tone'] === 'warn' ? 'is-warn' : '') }}"
                         style="width:{{ $sla['pct'] }}%"></div>
                </div>
            </div>
            <div class="tile-sub">{{ __('Due :date', ['date' => $fmt($sla['due'])]) }}</div>
        </div>
        <div class="tile">
            <div class="tile-label">{{ __('Rent fixed') }}</div>
            <div class="tile-value">{{ $a->assessed_monthly_rent ? $rs($a->assessed_monthly_rent) : '—' }}</div>
            <div class="tile-sub">{{ __('per month, by the District Officer') }}</div>
        </div>
        <div class="tile {{ (float) $arrears['balance'] > 0 ? 'is-danger' : '' }}">
            <div class="tile-label">{{ __('Arrears balance') }}</div>
            <div class="tile-value">{{ $rs($arrears['balance']) }}</div>
            <div class="tile-sub">{{ __('of :amount assessed', ['amount' => $rs($arrears['total_due'])]) }}</div>
        </div>
    </div>
@endif

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- What is being approved ---------- --}}
        <div class="card">
            <div class="card-head"><h3>{{ __('The case in brief') }}</h3></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>{{ __('Applicant') }}</dt><dd>{{ $a->applicant?->nameWithParentage(true) }}</dd>
                    <dt>{{ __('CNIC') }}</dt><dd class="num">{{ $a->applicant?->formattedCnic() }}</dd>
                    <dt>{{ __('Property') }}</dt><dd>{{ $p?->identity(true) }}, {{ $p?->address }}</dd>
                    <dt>{{ __('Area') }}</dt>
                    <dd>
                        @if ($p?->currentArea)
                            {{ __(':area sqft', ['area' => number_format((float) $p->currentArea->area_sqft, 2)]) }}
                            <span class="faint">{{ __('(:code standard)', ['code' => $a->unitProfile?->code]) }}</span>
                        @else — @endif
                    </dd>
                    <dt>{{ __('Possession since') }}</dt><dd>{{ $fmt($poss?->date_of_possession) }}</dd>
                    <dt>{{ __('Arrears run from') }}</dt><dd>{{ $fmt($poss?->arrears_from) }}</dd>
                    <dt>{{ __('District Officer') }}</dt><dd>{{ __($a->districtOfficer?->name ?? 'Not assigned') }}</dd>
                </dl>
            </div>
        </div>

        {{-- ---------- The DO reasoning ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Rent fixed by the District Officer') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Clause 10(i)(d)') }}</span></div>
            </div>
            <div class="card-body">
                @if ($decision)
                    <dl class="kv">
                        <dt>{{ __('Rent determined') }}</dt>
                        <dd><strong>{{ $rs($decision->determined_monthly_rent) }}</strong> {{ __('per month') }}</dd>
                        <dt>{{ __('Decided on') }}</dt><dd>{{ $fmt($decision->decided_at, 'd-m-Y H:i') }}</dd>
                        <dt>{{ __('Enhancement') }}</dt>
                        <dd>
                            {{ __(':rate% per annum, :method', [
                                'rate' => $round ? rtrim(rtrim($round->enhancement_rate, '0'), '.') : '8',
                                'method' => __($round ? strtolower($round->enhancement_method) : 'compound'),
                            ]) }}
                        </dd>
                    </dl>
                    <h4 class="mt-2">{{ __('Reasons recorded') }}</h4>
                    <p class="muted" style="white-space:pre-wrap;font-size:.88rem">{{ $decision->reasons }}</p>
                    @if ($decision->objections_considered)
                        <h4>{{ __('Objections considered') }}</h4>
                        <p class="muted" style="white-space:pre-wrap;font-size:.88rem">{{ $decision->objections_considered }}</p>
                    @endif
                @else
                    <div class="alert alert-warn">
                        @include('partials.icon', ['name' => 'alert'])
                        <div><p class="mb-0">{{ __('The District Officer has not recorded a determination of rent.') }}</p></div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ---------- Milestone rent ---------- --}}
        <div class="card">
            <div class="card-head"><h3>{{ __('Rent across the milestone years') }}</h3></div>
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead><tr><th>{{ __('Year') }}</th><th class="num">{{ __('Monthly') }}</th><th class="num">{{ __('Annual') }}</th></tr></thead>
                    <tbody>
                    @foreach ($milestones as $m)
                        <tr class="{{ $m['in_scope'] ? 'is-milestone' : 'row-muted' }}">
                            <td><strong>{{ $m['year'] }}</strong></td>
                            <td class="num">{{ $m['monthly_rent'] ? number_format((float) $m['monthly_rent'], 2) : '—' }}</td>
                            <td class="num">{{ $m['annual_rent'] ? number_format((float) $m['annual_rent'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ---------- Objections ---------- --}}
        @if ($a->objections->isNotEmpty())
            <div class="card">
                <div class="card-head">
                    <h3>{{ __('Objections and how they went') }}</h3>
                    <span class="badge badge-neutral">{{ $a->objections->count() }}</span>
                </div>
                <div class="card-body">
                    @foreach ($a->objections as $o)
                        @php $d = $objectionDecisions->get($o->id); @endphp
                        <div class="inline-list">
                            <strong>{{ $o->objector_name }}</strong>
                            @if ($d)
                                <span class="badge badge-{{ $d->decision === 'ACCEPTED' ? 'good' : ($d->decision === 'REJECTED' ? 'danger' : 'warn') }}">
                                    {{ __(ucwords(strtolower(str_replace('_', ' ', $d->decision)))) }}
                                </span>
                            @else
                                <span class="badge badge-warn">{{ __('Undecided') }}</span>
                            @endif
                        </div>
                        <p class="muted" style="font-size:.85rem;white-space:pre-wrap;margin:.4rem 0">{{ $o->plea }}</p>
                        @if ($d)
                            <p class="faint" style="font-size:.82rem;white-space:pre-wrap">{{ $d->reasons }}</p>
                        @endif
                        @if (! $loop->last)<hr class="divider">@endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div>
        {{-- ---------- Preconditions ---------- --}}
        <div class="card">
            <div class="card-head"><h3>{{ __('Preconditions') }}</h3></div>
            <div class="card-body">
                <ul class="timeline">
                    <li class="{{ $a->payment_status === 'PAID' ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('Rs. 5,000 deposit confirmed') }}</div>
                        <div class="t-meta">{{ __($a->payment_status ?? '') }}</div>
                    </li>
                    <li class="{{ $poss?->is_eligible ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('Possession prior to 01-01-2010') }}</div>
                        <div class="t-meta">{{ __('Clause 3(ii)(a)') }} &middot; {{ $fmt($poss?->date_of_possession) }}</div>
                    </li>
                    <li class="{{ $a->documents->where('status', 'VERIFIED')->count() ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('Documentary evidence verified') }}</div>
                        <div class="t-meta">
                            {{ __('Clause 3(ii)(c)') }} &middot;
                            {{ __(':done of :total verified', [
                                'done' => $a->documents->where('status', 'VERIFIED')->count(),
                                'total' => $a->documents->count(),
                            ]) }}
                        </div>
                    </li>
                    <li class="{{ $decision ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('Rent fixed for reasons') }}</div>
                        <div class="t-meta">{{ __('Clause 10(i)(d)') }}</div>
                    </li>
                    <li class="{{ $clearance['satisfied'] ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('Arrears cleared or provided for') }}</div>
                        <div class="t-meta">{{ __('Clause 3(ii)(b)') }} &middot; {{ __('balance :amount', ['amount' => $rs($arrears['balance'])]) }}</div>
                    </li>
                    <li class="{{ ! $a->is_sub_judice ? 'is-done' : '' }}">
                        <div class="t-title">{{ __('No case pending, no stay in force') }}</div>
                        <div class="t-meta">{{ $a->is_sub_judice ? __('Sub judice') : __('Clear') }}</div>
                    </li>
                </ul>

                <div class="alert {{ $clearance['satisfied'] ? 'alert-good' : 'alert-warn' }}" style="margin:.9rem 0 0">
                    @include('partials.icon', ['name' => $clearance['satisfied'] ? 'check' : 'alert'])
                    <div><p class="mb-0">{{ __($clearance['reason']) }}</p></div>
                </div>
            </div>
        </div>

        {{-- ---------- Decide ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ $decided ? __('Decision recorded') : __('Record your decision') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(d)') }}</span></div>
            </div>
            <div class="card-body">
                @foreach ($a->approvals->where('level', 'ADMINISTRATOR') as $ap)
                    <div class="inline-list">
                        <span class="badge badge-{{ $ap->action === 'APPROVE' ? 'good' : ($ap->action === 'REJECT' ? 'danger' : 'warn') }}">
                            {{ __(ucfirst(strtolower($ap->action))) }}
                        </span>
                        @if (! $ap->is_within_sla)
                            <span class="badge badge-danger">{{ __('Beyond one month') }}</span>
                        @endif
                        <span class="faint" style="font-size:.8rem">{{ $fmt($ap->acted_at, 'd-m-Y H:i') }}</span>
                    </div>
                    <p class="muted" style="white-space:pre-wrap;font-size:.86rem;margin:.5rem 0">{{ $ap->reasons }}</p>
                    @if ($ap->conditions)
                        <h4>{{ __('Conditions') }}</h4>
                        <p class="muted" style="white-space:pre-wrap;font-size:.86rem">{{ $ap->conditions }}</p>
                    @endif
                    <hr class="divider">
                @endforeach

                @can('do', 'approvals.administrator')
                    @unless ($decided)
                        @unless ($canApprove['allowed'])
                            <div class="alert alert-warn">
                                @include('partials.icon', ['name' => 'alert'])
                                <div>
                                    <strong>{{ __('Approval is currently blocked:') }}</strong>
                                    <ul>
                                        @foreach ($canApprove['reasons'] as $r)
                                            <li>{{ __($r) }}</li>
                                        @endforeach
                                    </ul>
                                    <p class="mb-0">{{ __('You may still reject or remand.') }}</p>
                                </div>
                            </div>
                        @endunless

                        <form method="POST" action="{{ route('approvals.store', $a) }}">
                            @csrf
                            <div class="field">
                                <label for="action">{{ __('Decision') }} <span class="req">*</span></label>
                                <select id="action" name="action" class="select">
                                    <option value="APPROVE" @disabled(! $canApprove['allowed'])>
                                        {{ __('Approve the regularization') }}
                                    </option>
                                    <option value="REMAND">{{ __('Remand to the District Officer') }}</option>
                                    <option value="REJECT">{{ __('Reject') }}</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="reasons">{{ __('Reasons') }} <span class="req">*</span></label>
                                <textarea id="reasons" name="reasons" class="textarea" style="min-height:170px"
                                          required minlength="40">{{ old('reasons') }}</textarea>
                                <p class="hint">
                                    {!! __('Clause 3(ii)(d) requires the approval to be made <strong>after recording reasons</strong>.') !!}
                                    {{ __('Set out what you considered — eligibility, the evidence, the rent fixed and why it is sound, the objections, the arrears position.') }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="conditions">{{ __('Conditions attached') }}</label>
                                <textarea id="conditions" name="conditions" class="textarea"
                                          style="min-height:70px">{{ old('conditions') }}</textarea>
                            </div>

                            <div class="field">
                                <label for="order_reference">{{ __('Order reference') }}</label>
                                <input type="text" id="order_reference" name="order_reference" class="input"
                                       maxlength="120" value="{{ old('order_reference') }}">
                            </div>

                            <button class="btn btn-primary btn-lg" type="submit" style="width:100%">
                                @include('partials.icon', ['name' => 'check']) {{ __('Record decision') }}
                            </button>
                        </form>
                    @endunless
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection
