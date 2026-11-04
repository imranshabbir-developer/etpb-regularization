@extends('layouts.app')

@section('title', __('Consolidated report'))
@section('heading', __('Consolidated / master report'))

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100) : 0;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
    $allBreaches = collect($breaches['assessment'])->concat(collect($breaches['approval']));
@endphp

<div class="page-head">
    <h1>{{ __('Consolidated report') }}</h1>
    <p class="lede">
        {{ __('Evacuee Trust Property Board') }} &middot;
        {{ $districtName ? __('District :name', ['name' => __($districtName)]) : __('All districts') }} &middot;
        {{ __('as at :date', ['date' => $generatedAt->translatedFormat('d F Y, H:i')]) }}
    </p>
</div>

<div class="card no-print">
    <div class="card-body tight">
        <form method="GET" action="{{ route('reports.executive') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="field mb-0 min-w-[200px]">
                <label for="district">{{ __('District') }}</label>
                <select id="district" name="district" class="select" onchange="this.form.submit()">
                    <option value="">{{ __('All districts') }}</option>
                    @foreach ($districts as $d)
                        <option value="{{ $d->id }}" @selected($districtId === $d->id)>{{ __($d->name) }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn btn-ghost btn-sm" href="{{ route('reports.glimpse') }}">{{ __('At a glance') }}</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('reports.registers') }}">{{ __('Registers') }}</a>
        </form>
        <div class="mt-2">
            @include('partials.report-formats', [
                'route' => route('reports.executive'),
                'query' => array_filter(['district' => $districtId]),
            ])
        </div>
    </div>
</div>

{{-- ---------- Headline ---------- --}}
<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('Applications received') }}</div>
        <div class="tile-value">{{ number_format($h['all']) }}</div>
        <div class="tile-sub">{{ __(':count still open', ['count' => number_format($h['open'])]) }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Regularized') }}</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">{{ __(':pct% of all received', ['pct' => $pct($h['regularized'], $h['all'])]) }}</div>
    </div>
    <div class="tile {{ $h['pending_pay'] > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Awaiting deposit') }}</div>
        <div class="tile-value">{{ number_format($h['pending_pay']) }}</div>
        <div class="tile-sub">{{ __('not processed until paid') }}</div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">{{ __('Fee collected') }}</div>
        <div class="tile-value">{{ $rs($h['fee_total']) }}</div>
        <div class="tile-sub">{{ __(':count instruments confirmed', ['count' => number_format($h['fee_count'])]) }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Monthly rent secured') }}</div>
        <div class="tile-value">{{ $rs($h['monthly_rent']) }}</div>
        <div class="tile-sub">{{ __(':amount annualised', ['amount' => $rs((float) $h['monthly_rent'] * 12)]) }}</div>
    </div>
    <div class="tile {{ (float) $h['outstanding'] > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">{{ __('Arrears outstanding') }}</div>
        <div class="tile-value">{{ $rs($h['outstanding']) }}</div>
        <div class="tile-sub">
            {{ __('of :assessed assessed · :rate% recovered', ['assessed' => $rs($h['assessed']), 'rate' => $h['recovery_rate']]) }}
        </div>
    </div>
    <div class="tile {{ $h['sub_judice'] > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Sub judice') }}</div>
        <div class="tile-value">{{ number_format($h['sub_judice']) }}</div>
        <div class="tile-sub">{{ __('pending before a court or stayed') }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Area regularized') }}</div>
        <div class="tile-value">{{ number_format((float) $h['area_sqft'], 0) }}</div>
        <div class="tile-sub">
            {{ __('sqft · about :kanal Kanal', ['kanal' => number_format((float) $h['area_sqft'] / 5445, 2)]) }}
        </div>
    </div>
</div>

{{-- ---------- Statutory deadlines ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>{{ __('Statutory deadlines breached') }}</h3>
        <span class="badge badge-{{ $allBreaches->count() ? 'danger' : 'good' }}">
            {{ $allBreaches->count() }}
        </span>
        <div class="card-actions">
            <span class="clause">{{ __('Clause 10(i)(e)') }}</span>
            <span class="clause">{{ __('Clause 3(ii)(d)') }}</span>
        </div>
    </div>

    @if ($allBreaches->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">{{ __('No statutory deadline is currently breached.') }}</p>
        </div>
    @else
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr>
                    <th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('District') }}</th>
                    <th>{{ __('Deadline') }}</th><th>{{ __('Overdue by') }}</th><th>{{ __('Officer answerable') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($breaches['assessment'] as $a)
                    @php $due = \Illuminate\Support\Carbon::parse($a->assessment_extended_to ?: $a->assessment_due_date); @endphp
                    <tr>
                        <td class="nowrap"><a href="{{ route('applications.show', $a) }}">{{ $a->application_no }}</a></td>
                        <td>{{ $a->applicant?->full_name }}</td>
                        <td>{{ __($a->district?->name ?? '') }}</td>
                        <td class="nowrap">{{ __('Assessment') }} &mdash; {{ $due->translatedFormat('d-m-Y') }}</td>
                        <td class="nowrap">
                            <span class="badge badge-danger">{{ __(':count days', ['count' => (int) $due->diffInDays(now())]) }}</span>
                        </td>
                        <td class="{{ $a->districtOfficer ? '' : 'row-muted' }}">
                            {{ __($a->districtOfficer?->name ?? 'Not assigned') }}
                        </td>
                    </tr>
                @endforeach
                @foreach ($breaches['approval'] as $a)
                    @php $due = \Illuminate\Support\Carbon::parse($a->admin_approval_due_date); @endphp
                    <tr>
                        <td class="nowrap"><a href="{{ route('applications.show', $a) }}">{{ $a->application_no }}</a></td>
                        <td>{{ $a->applicant?->full_name }}</td>
                        <td>{{ __($a->district?->name ?? '') }}</td>
                        <td class="nowrap">{{ __('Approval') }} &mdash; {{ $due->translatedFormat('d-m-Y') }}</td>
                        <td class="nowrap">
                            <span class="badge badge-danger">{{ __(':count days', ['count' => (int) $due->diffInDays(now())]) }}</span>
                        </td>
                        <td class="{{ $a->administrator ? '' : 'row-muted' }}">
                            {{ __($a->administrator?->name ?? 'Not assigned') }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ---------- Deadline performance ---------- --}}
<div class="card">
    <div class="card-head"><h3>{{ __('Deadline performance') }}</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>{{ __('Deadline') }}</th><th class="num">{{ __('In hand') }}</th><th class="num">{{ __('Overdue') }}</th>
                <th class="num">{{ __('On time') }}</th><th>{{ __('Authority') }}</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ __('Assessment within 60 days of first notice') }}</td>
                <td class="num">{{ number_format($p['assessment_live']) }}</td>
                <td class="num">{{ number_format($p['assessment_overdue']) }}</td>
                <td class="num"><strong>{{ $p['assessment_ontime'] }}%</strong></td>
                <td><span class="clause">{{ __('Clause 10(i)(e)') }}</span></td>
            </tr>
            <tr>
                <td>{{ __('Administrator approval within one month') }}</td>
                <td class="num">{{ number_format($p['approval_live']) }}</td>
                <td class="num">{{ number_format($p['approval_overdue']) }}</td>
                <td class="num"><strong>{{ $p['approval_ontime'] }}%</strong></td>
                <td><span class="clause">{{ __('Clause 3(ii)(d)') }}</span></td>
            </tr>
            <tr>
                <td>{{ __('Approvals decided within the month, to date') }}</td>
                <td class="num">{{ number_format($p['approvals_total']) }}</td>
                <td class="num">{{ number_format($p['approvals_total'] - $p['approvals_in_time']) }}</td>
                <td class="num"><strong>{{ $p['approvals_pct'] }}%</strong></td>
                <td><span class="clause">{{ __('Clause 3(ii)(d)') }}</span></td>
            </tr>
            </tbody>
        </table>
    </div>
    @if ($p['avg_days'] !== null)
        <div class="card-foot">
            <p class="mb-0 faint text-[.8rem]">
                {{ __('A case takes on average :count days from submission to regularization.', ['count' => number_format($p['avg_days'])]) }}
            </p>
        </div>
    @endif
</div>

{{-- ---------- District league table ---------- --}}
<div class="card">
    <div class="card-head"><h3>{{ __('By district') }}</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr>
                <th>{{ __('District') }}</th>
                <th class="num">{{ __('Received') }}</th><th class="num">{{ __('Paid') }}</th><th class="num">{{ __('Regularized') }}</th>
                <th class="num">{{ __('Sub judice') }}</th>
                <th class="num">{{ __('Assessed') }}</th><th class="num">{{ __('Recovered') }}</th><th class="num">{{ __('Outstanding') }}</th>
                <th class="num">{{ __('Recovery') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($byDistrict as $d)
                <tr>
                    <td>{{ __($d->district?->name ?? 'Not recorded') }}</td>
                    <td class="num">{{ number_format($d->total) }}</td>
                    <td class="num">{{ number_format($d->paid) }}</td>
                    <td class="num">{{ number_format($d->regularized) }}</td>
                    <td class="num">{{ number_format($d->sub_judice) }}</td>
                    <td class="num">{{ number_format((float) $d->assessed, 0) }}</td>
                    <td class="num">{{ number_format((float) $d->recovered, 0) }}</td>
                    <td class="num"><strong>{{ number_format((float) $d->outstanding, 0) }}</strong></td>
                    <td class="num">{{ $pct((float) $d->recovered, (float) $d->assessed) }}%</td>
                </tr>
            @empty
                <tr><td colspan="9" class="row-muted">{{ __('No applications yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    {{-- ---------- Caseload by stage ---------- --}}
    <div class="card">
        <div class="card-head"><h3>{{ __('Caseload by stage') }}</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>{{ __('Stage') }}</th><th class="num">{{ __('Count') }}</th><th class="num">{{ __('Share') }}</th></tr></thead>
                <tbody>
                @forelse ($h['by_status'] as $status => $count)
                    <tr>
                        <td>
                            <span class="badge badge-{{ \App\Services\WorkflowService::TONES[$status] ?? 'neutral' }}">
                                {{ __($labels[$status] ?? $status) }}
                            </span>
                        </td>
                        <td class="num">{{ number_format($count) }}</td>
                        <td class="num">{{ $pct($count, $h['all']) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="row-muted">{{ __('No applications yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---------- Objections ---------- --}}
    <div class="card">
        <div class="card-head">
            <h3>{{ __('Objections') }}</h3>
            <div class="card-actions"><span class="clause">{{ __('Clause 10(i)(c)–(d)') }}</span></div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>{{ __('Filed') }}</dt><dd>{{ number_format($objections['filed']) }}</dd>
                <dt>{{ __('Decided') }}</dt><dd>{{ number_format($objections['decided']) }}</dd>
                <dt>{{ __('Still open') }}</dt><dd><strong>{{ number_format($objections['open']) }}</strong></dd>
                <dt>{{ __('Filed out of time') }}</dt><dd>{{ number_format($objections['late']) }}</dd>
            </dl>
            @if ($objections['outcomes']->isNotEmpty())
                <hr class="divider">
                <div class="inline-list">
                    @foreach ($objections['outcomes'] as $decision => $n)
                        <span class="badge badge-{{ $decision === 'ACCEPTED' ? 'good' : ($decision === 'REJECTED' ? 'danger' : 'warn') }}">
                            {{ __(ucwords(strtolower(str_replace('_', ' ', $decision)))) }} <strong>{{ $n }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ---------- Intake vs disposal + arrears ageing ---------- --}}
@php
    $trend = [
        'labels' => $monthly->keys()->values(),
        'intake' => $monthly->values()->values(),
        'disposal' => ($disposal ?? collect())->values()->values(),
        'intakeLabel' => __('Submitted'),
        'disposalLabel' => __('Regularized'),
    ];
    $ageChart = [
        'labels' => collect($ageing)->pluck('label')->map(fn ($l) => __($l))->values(),
        'values' => collect($ageing)->map(fn ($b) => (float) $b['amount'])->values(),
        'label' => __('Outstanding'),
        'money' => true,
    ];
    $ageTotal = collect($ageing)->sum(fn ($b) => (float) $b['amount']);
@endphp

<div class="grid-2 items-start gap-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>{{ __('Intake vs regularized (12 months)') }}</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame">
                <canvas data-etpb-chart="line-dual" data-etpb-source="exec-report-trend"
                        aria-label="{{ __('Monthly intake and regularization') }}"></canvas>
            </div>
            <script type="application/json" id="exec-report-trend">@json($trend)</script>
        </div>
    </div>
    <div class="card">
        <div class="card-head">
            <h3>{{ __('Arrears ageing') }}</h3>
            <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(b)') }}</span></div>
        </div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="bar" data-etpb-source="exec-report-age"
                        aria-label="{{ __('Outstanding arrears by age') }}"></canvas>
            </div>
            <script type="application/json" id="exec-report-age">@json($ageChart)</script>
        </div>
        <div class="table-wrap border-0 rounded-none border-t">
            <table class="data">
                <thead>
                <tr><th>{{ __('Age') }}</th><th class="num">{{ __('Cases') }}</th><th class="num">{{ __('Outstanding') }}</th><th class="num">{{ __('Share') }}</th></tr>
                </thead>
                <tbody>
                @foreach ($ageing as $bucket)
                    <tr>
                        <td>{{ __($bucket['label']) }}</td>
                        <td class="num">{{ number_format($bucket['count']) }}</td>
                        <td class="num"><strong>{{ $rs($bucket['amount']) }}</strong></td>
                        <td class="num">{{ $pct((float) $bucket['amount'], $ageTotal) }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ---------- Fee by instrument + litigation ---------- --}}
<div class="grid-2 items-start gap-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>{{ __('Fee collection by instrument') }}</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>{{ __('Instrument') }}</th><th class="num">{{ __('Count') }}</th><th class="num">{{ __('Amount') }}</th></tr></thead>
                <tbody>
                @forelse ($feeBreakdown as $row)
                    <tr>
                        <td>{{ __(str_replace('_', ' ', $row->instrument_type)) }}</td>
                        <td class="num">{{ number_format($row->n) }}</td>
                        <td class="num">{{ $rs($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="row-muted">{{ __('No verified instruments yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>{{ __('Litigation & restraining orders') }}</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>{{ __('Cases on register') }}</dt><dd>{{ number_format($litigation['total']) }}</dd>
                <dt>{{ __('Pending') }}</dt><dd>{{ number_format($litigation['pending']) }}</dd>
                <dt>{{ __('With restraining order') }}</dt><dd><strong>{{ number_format($litigation['stays']) }}</strong></dd>
                <dt>{{ __('Direction cases') }}</dt><dd>{{ number_format($litigation['direction']) }}</dd>
            </dl>
            @if ($litigation['upcoming']->isNotEmpty())
                <hr class="divider">
                <p class="text-[.8rem] faint mb-2">{{ __('Next hearings') }}</p>
                <ul class="text-[.85rem] space-y-1 mb-0">
                    @foreach ($litigation['upcoming'] as $u)
                        <li>
                            <strong>{{ $u->application_no ?: '—' }}</strong>
                            — {{ \Illuminate\Support\Carbon::parse($u->next_hearing_date)->translatedFormat('d-m-Y') }}
                            <span class="faint">({{ $u->court_name }})</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

{{-- ---------- Disposal trend ---------- --}}
<div class="card">
    <div class="card-head"><h3>{{ __('Cases regularized, last 12 months') }}</h3></div>
    <div class="card-body">
        @php $dmax = max($disposal->values()->all() ?: [1]); @endphp
        <div class="flex items-end gap-1 sm:gap-2 h-[150px]">
            @foreach ($disposal as $ym => $n)
                <div class="flex-1 flex flex-col items-center gap-1 h-full min-w-0">
                    <div class="text-[.72rem] tabular-nums muted">{{ $n }}</div>
                    <div class="w-full rounded-t mt-auto"
                         style="height:{{ $dmax > 0 ? max(3, round($n / $dmax * 100)) : 3 }}%; background: var(--color-warn-600, #b45309)"></div>
                    <div class="faint text-[.62rem] sm:text-[.68rem] whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M y') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
