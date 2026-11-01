@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

@php
    $first = explode(' ', trim(auth()->user()->name))[0];
    $officerStageData = [
        'labels' => $byStatus->keys()->map(fn ($s) => $labels[$s] ?? $s)->values(),
        'values' => $byStatus->values()->map(fn ($n) => (int) $n)->values(),
        'centerLabel' => 'Cases',
    ];
    $queueNumeric = collect($work)->filter(fn ($t) => is_numeric($t['value']))->values();
    $queueBar = [
        'labels' => $queueNumeric->pluck('label')->values(),
        'values' => $queueNumeric->map(fn ($t) => (int) $t['value'])->values(),
        'label' => 'Queue load',
    ];
    $trend = [
        'labels' => $monthly->keys()->values(),
        'intake' => $monthly->values()->values(),
        'disposal' => $disposal->values()->values(),
        'intakeLabel' => 'Submitted',
        'disposalLabel' => 'Regularized',
    ];
@endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ $first }}</h1>
    <p class="lede">
        {{ auth()->user()->primaryRole()?->name }}
        @if (auth()->user()->district) &middot; {{ auth()->user()->district->name }} @endif
    </p>
</div>

@if (! empty($work))
    <div class="tiles">
        @foreach ($work as $tile)
            <a href="{{ $tile['route'] }}" class="tile {{ $tile['tone'] }} no-underline hover:no-underline block">
                <div class="tile-label">{{ $tile['label'] }}</div>
                <div class="tile-value">{{ is_numeric($tile['value']) ? number_format($tile['value']) : $tile['value'] }}</div>
                <div class="tile-sub">{{ $tile['sub'] }}</div>
            </a>
        @endforeach
    </div>
@endif

<div class="grid-2 items-start gap-[1.15rem] mb-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>Intake vs regularized</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="line-dual" data-etpb-source="officer-trend-data"
                        aria-label="Monthly intake and regularization"></canvas>
            </div>
            <script type="application/json" id="officer-trend-data">@json($trend)</script>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Caseload by stage</h3></div>
        <div class="card-body chart-card-body">
            @if ($byStatus->isEmpty())
                <p class="muted mb-0">No applications yet.</p>
            @else
                <div class="chart-frame is-md">
                    <canvas data-etpb-chart="doughnut" data-etpb-source="officer-stage-data"
                            aria-label="Caseload by stage"></canvas>
                </div>
                <script type="application/json" id="officer-stage-data">@json($officerStageData)</script>
            @endif
        </div>
    </div>
</div>

@if ($queueNumeric->isNotEmpty())
<div class="card mb-[1.15rem]">
    <div class="card-head"><h3>Work queues at a glance</h3></div>
    <div class="card-body chart-card-body">
        <div class="chart-frame is-sm">
            <canvas data-etpb-chart="hbar" data-etpb-source="officer-queue-data"
                    aria-label="Work queue volumes"></canvas>
        </div>
        <script type="application/json" id="officer-queue-data">@json($queueBar)</script>
    </div>
</div>
@endif

<div class="card">
    <div class="card-head">
        <h3>Recent applications</h3>
        <div class="card-actions">
            <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">All</a>
        </div>
    </div>

    @if ($recent->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'empty'])
            <p class="mb-0">Nothing yet.</p>
        </div>
    @else
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>Application</th><th>Applicant</th><th>Stage</th><th>Updated</th></tr>
                </thead>
                <tbody>
                @foreach ($recent as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                        <td class="nowrap faint" title="{{ $app->updated_at?->format('d-m-Y H:i') }}">{{ $app->updated_at?->format('d M') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
