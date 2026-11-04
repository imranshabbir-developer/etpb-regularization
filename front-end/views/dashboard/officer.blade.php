@extends('layouts.app')

@section('title', __('Dashboard'))
@section('heading', __('Dashboard'))

@section('content')

@php
    $first = auth()->user()->greetingName();
    $officerStageData = [
        'labels' => $byStatus->keys()->map(fn ($s) => __($labels[$s] ?? $s))->values(),
        'values' => $byStatus->values()->map(fn ($n) => (int) $n)->values(),
        'centerLabel' => __('Cases'),
    ];
    $queueNumeric = collect($work)->filter(fn ($t) => is_numeric($t['value']))->values();
    $queueBar = [
        'labels' => $queueNumeric->map(fn ($t) => __($t['label']))->values(),
        'values' => $queueNumeric->map(fn ($t) => (int) $t['value'])->values(),
        'label' => __('Queue load'),
    ];
    $trend = [
        'labels' => $monthly->keys()->values(),
        'intake' => $monthly->values()->values(),
        'disposal' => $disposal->values()->values(),
        'intakeLabel' => __('Submitted'),
        'disposalLabel' => __('Regularized'),
    ];
@endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? __('Good morning') : (now()->hour < 17 ? __('Good afternoon') : __('Good evening')) }},
        {{ $first }}</h1>
    <p class="lede">
        {{ __(auth()->user()->primaryRole()?->name ?? '') }}
        @if (auth()->user()->district) &middot; {{ __(auth()->user()->district->name) }} @endif
    </p>
</div>

@if (! empty($work))
    <div class="tiles">
        @foreach ($work as $tile)
            <a href="{{ $tile['route'] }}" class="tile {{ $tile['tone'] }} no-underline hover:no-underline block">
                <div class="tile-label">{{ __($tile['label']) }}</div>
                <div class="tile-value">{{ is_numeric($tile['value']) ? number_format($tile['value']) : $tile['value'] }}</div>
                <div class="tile-sub">{{ __($tile['sub'] ?? '') }}</div>
            </a>
        @endforeach
    </div>
@endif

<div class="grid-2 items-start gap-[1.15rem] mb-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>{{ __('Intake vs regularized') }}</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="line-dual" data-etpb-source="officer-trend-data"
                        aria-label="{{ __('Monthly intake and regularization') }}"></canvas>
            </div>
            <script type="application/json" id="officer-trend-data">@json($trend)</script>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>{{ __('Caseload by stage') }}</h3></div>
        <div class="card-body chart-card-body">
            @if ($byStatus->isEmpty())
                <p class="muted mb-0">{{ __('No applications yet.') }}</p>
            @else
                <div class="chart-frame is-md">
                    <canvas data-etpb-chart="doughnut" data-etpb-source="officer-stage-data"
                            aria-label="{{ __('Caseload by stage') }}"></canvas>
                </div>
                <script type="application/json" id="officer-stage-data">@json($officerStageData)</script>
            @endif
        </div>
    </div>
</div>

@if ($queueNumeric->isNotEmpty())
<div class="card mb-[1.15rem]">
    <div class="card-head"><h3>{{ __('Work queues at a glance') }}</h3></div>
    <div class="card-body chart-card-body">
        <div class="chart-frame is-sm">
            <canvas data-etpb-chart="hbar" data-etpb-source="officer-queue-data"
                    aria-label="{{ __('Work queue volumes') }}"></canvas>
        </div>
        <script type="application/json" id="officer-queue-data">@json($queueBar)</script>
    </div>
</div>
@endif

<div class="card">
    <div class="card-head">
        <h3>{{ __('Recent applications') }}</h3>
        <div class="card-actions">
            <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">{{ __('All') }}</a>
        </div>
    </div>

    @if ($recent->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'empty'])
            <p class="mb-0">{{ __('Nothing yet.') }}</p>
        </div>
    @else
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('Stage') }}</th><th>{{ __('Updated') }}</th></tr>
                </thead>
                <tbody>
                @foreach ($recent as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                        <td class="nowrap faint" title="{{ $app->updated_at?->translatedFormat('d-m-Y H:i') }}">{{ $app->updated_at?->translatedFormat('d M') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
