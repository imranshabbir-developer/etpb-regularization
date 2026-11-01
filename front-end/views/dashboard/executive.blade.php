@extends('layouts.app')

@section('title', 'Overview')
@section('heading', 'Overview')

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $first = explode(' ', trim(auth()->user()->name))[0];
    $short = function ($n) {
        $n = (float) $n;
        if ($n >= 10000000) return 'Rs. ' . number_format($n / 10000000, 2) . ' cr';
        if ($n >= 100000)   return 'Rs. ' . number_format($n / 100000, 2) . ' lac';
        return 'Rs. ' . number_format($n, 0);
    };
    $breachCount = ($breaches['assessment']->count() ?? 0) + ($breaches['approval']->count() ?? 0);

    $trend = [
        'labels' => $monthly->keys()->values(),
        'intake' => $monthly->values()->values(),
        'disposal' => $disposal->values()->values(),
        'intakeLabel' => 'Submitted',
        'disposalLabel' => 'Regularized',
    ];
    $ageChart = [
        'labels' => collect($ageing)->pluck('label')->values(),
        'values' => collect($ageing)->map(fn ($b) => (float) $b['amount'])->values(),
        'label' => 'Outstanding',
        'money' => true,
    ];
    $districtChart = [
        'labels' => $byDistrict->take(6)->map(fn ($d) => $d->district?->name ?? '—')->values(),
        'values' => $byDistrict->take(6)->map(fn ($d) => (int) $d->total)->values(),
        'label' => 'Cases',
    ];
    $attention = [
        'labels' => ['Awaiting deposit', 'Sub judice', 'Objections', 'Stays'],
        'values' => [
            (int) $h['pending_pay'],
            (int) $h['sub_judice'],
            (int) ($objections['open'] ?? 0),
            (int) ($litigation['stays'] ?? 0),
        ],
        'colors' => ['#E8891A', '#3B7DD4', '#C4680A', '#B3261E'],
        'centerLabel' => 'Flags',
    ];
@endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ $first }}</h1>
    <p class="lede">
        {{ auth()->user()->primaryRole()?->name }}
        @cannot('do', 'records.correct')
            &middot; <span class="badge badge-info">View only</span>
        @endcannot
    </p>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">Open cases</div>
        <div class="tile-value">{{ number_format($h['open']) }}</div>
        <div class="tile-sub">{{ number_format($h['all']) }} total &middot; {{ $h['disposal_rate'] }}% disposed</div>
    </div>
    <div class="tile">
        <div class="tile-label">Regularized</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">{{ $short((float) $h['monthly_rent'] * 12) }} / year secured</div>
    </div>
    <div class="tile {{ $h['recovery_rate'] < 50 ? 'is-warn' : '' }}">
        <div class="tile-label">Arrears recovery</div>
        <div class="tile-value">{{ $h['recovery_rate'] }}%</div>
        <div class="tile-sub">{{ $short($h['outstanding']) }} outstanding</div>
    </div>
    <div class="tile {{ $breachCount > 0 ? 'is-danger' : 'is-gold' }}">
        <div class="tile-label">SLA breaches</div>
        <div class="tile-value">{{ number_format($breachCount) }}</div>
        <div class="tile-sub">assessment + approval</div>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem] mb-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>Intake vs disposal (12 months)</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame">
                <canvas data-etpb-chart="line-dual" data-etpb-source="exec-trend-data"
                        aria-label="Monthly intake and regularization"></canvas>
            </div>
            <script type="application/json" id="exec-trend-data">@json($trend)</script>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Needs attention</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="doughnut" data-etpb-source="exec-attention-data"
                        aria-label="Items needing attention"></canvas>
            </div>
            <script type="application/json" id="exec-attention-data">@json($attention)</script>
            <div class="btn-row mt-3">
                <a href="{{ route('reports.executive') }}" class="btn btn-outline btn-sm">Consolidated report</a>
                <a href="{{ route('reports.registers') }}" class="btn btn-ghost btn-sm">Registers</a>
            </div>
        </div>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem] mb-[1.15rem]">
    <div class="card">
        <div class="card-head">
            <h3>Deadline performance</h3>
            @if ($p['assessment_overdue'] + $p['approval_overdue'] > 0)
                <span class="badge badge-danger">{{ $p['assessment_overdue'] + $p['approval_overdue'] }} overdue</span>
            @else
                <span class="badge badge-good">On time</span>
            @endif
        </div>
        <div class="card-body">
            @foreach ([
                ['Assessment (60 days)', $p['assessment_ontime'], $p['assessment_overdue']],
                ['Approval (1 month)', $p['approval_ontime'], $p['approval_overdue']],
            ] as [$label, $pct, $over])
                <div class="mb-4 last:mb-0">
                    <div class="flex items-baseline justify-between gap-2">
                        <strong class="text-[.88rem]">{{ $label }}</strong>
                        <span class="tabular-nums text-[.88rem] font-semibold">{{ $pct }}%</span>
                    </div>
                    <div class="sla-bar mt-2">
                        <div class="sla-fill {{ $pct >= 90 ? '' : ($pct >= 70 ? 'is-warn' : 'is-danger') }}"
                             style="width:{{ $pct }}%"></div>
                    </div>
                    @if ($over > 0)
                        <div class="tile-sub text-danger-600">{{ number_format($over) }} past the limit</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="card-foot">
            <a href="{{ route('reports.glimpse') }}" class="btn btn-primary btn-sm">
                Full glimpse @include('partials.icon', ['name' => 'arrow-right'])
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Arrears ageing</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="bar" data-etpb-source="exec-age-data"
                        aria-label="Outstanding arrears by age"></canvas>
            </div>
            <script type="application/json" id="exec-age-data">@json($ageChart)</script>
        </div>
    </div>
</div>

<div class="card mb-[1.15rem]">
    <div class="card-head"><h3>Busiest districts</h3></div>
    <div class="card-body chart-card-body">
        @if ($byDistrict->isEmpty())
            <p class="muted mb-0">No applications yet.</p>
        @else
            <div class="chart-frame is-sm">
                <canvas data-etpb-chart="hbar" data-etpb-source="exec-district-data"
                        aria-label="Cases by district"></canvas>
            </div>
            <script type="application/json" id="exec-district-data">@json($districtChart)</script>
        @endif
    </div>
</div>

@if ($recent->isNotEmpty())
<div class="card">
    <div class="card-head">
        <h3>Recent activity</h3>
        <div class="card-actions">
            <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">All</a>
        </div>
    </div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>Application</th><th>Applicant</th><th>District</th><th>Status</th><th>Updated</th></tr>
            </thead>
            <tbody>
            @foreach ($recent->take(5) as $app)
                <tr>
                    <td class="nowrap"><a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a></td>
                    <td>{{ $app->applicant?->full_name }}</td>
                    <td>{{ $app->district?->name }}</td>
                    <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                    <td class="nowrap faint">{{ $app->updated_at?->diffForHumans() }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
