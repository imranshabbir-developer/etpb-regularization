@extends('layouts.app')

@section('title', __('Approval queue'))
@section('heading', __('Pending approval'))

@section('content')

<div class="page-head">
    <h1>{{ __('Applications awaiting approval') }}</h1>
    <p class="lede">
        {{ __('Regularization is approved by the Administrator within one month of the decision, after recording reasons.') }}
        <span class="clause">{{ __('Clause 3(ii)(d)') }}</span>
    </p>
</div>

@php
    $awaiting = (int) ($stats['awaiting'] ?? $applications->total());
    $dueSoon = (int) ($stats['due_soon'] ?? 0);
    $overdue = (int) ($stats['overdue'] ?? 0);
    $onTrack = max(0, $awaiting - $dueSoon - $overdue);
    $approvalChart = [
        'labels' => [__('On track'), __('Due in 7 days'), __('Past SLA')],
        'values' => [$onTrack, $dueSoon, $overdue],
        'colors' => ['#2F66B0', '#E8891A', '#B3261E'],
        'centerLabel' => __('Queue'),
        'centerValue' => $awaiting,
    ];
@endphp

<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('Awaiting decision') }}</div>
        <div class="tile-value">{{ number_format($awaiting) }}</div>
        <div class="tile-sub">{{ __("On the Administrator's desk") }}</div>
    </div>
    <div class="tile {{ $dueSoon > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Due within 7 days') }}</div>
        <div class="tile-value">{{ number_format($dueSoon) }}</div>
        <div class="tile-sub">{{ __('One-month SLA closing soon') }}</div>
    </div>
    <div class="tile {{ $overdue > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">{{ __('Past the SLA') }}</div>
        <div class="tile-value">{{ number_format($overdue) }}</div>
        <div class="tile-sub">{{ __('Beyond the one-month limit') }}</div>
    </div>
</div>

@if ($awaiting > 0)
<div class="card mb-[1.15rem]">
    <div class="card-head"><h3>{{ __('Approval SLA mix') }}</h3></div>
    <div class="card-body chart-card-body">
        <div class="chart-frame is-sm">
            <canvas data-etpb-chart="doughnut" data-etpb-source="approval-sla-data"
                    aria-label="{{ __('Approval queue by SLA status') }}"></canvas>
        </div>
        <script type="application/json" id="approval-sla-data">@json($approvalChart)</script>
    </div>
</div>
@endif

<div class="card">
    <div class="card-head">
        <h3>{{ __(':count awaiting decision', ['count' => number_format($applications->total())]) }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.executive') }}" class="btn btn-outline btn-sm">{{ __('Executive report') }}</a>
        </div>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">{{ __('Nothing is waiting on a decision.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('District') }}</th>
                    <th class="num">{{ __('Rent fixed') }}</th><th class="num">{{ __('Arrears balance') }}</th>
                    <th>{{ __('Due by') }}</th><th>{{ __('District Officer') }}</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    @php
                        $due = $app->admin_approval_due_date;
                        $left = $due ? (int) now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($due), false) : null;
                        $tone = $left === null ? 'neutral' : ($left < 0 ? 'danger' : ($left <= 7 ? 'warn' : 'good'));
                    @endphp
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('approvals.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ __($app->district?->name ?? '') }}</td>
                        <td class="num">
                            {{ $app->assessed_monthly_rent ? 'Rs. ' . number_format((float) $app->assessed_monthly_rent, 0) : '—' }}
                        </td>
                        <td class="num">
                            @if ((float) $app->arrears_balance > 0)
                                <strong>Rs. {{ number_format((float) $app->arrears_balance, 0) }}</strong>
                            @else
                                <span class="badge badge-good">{{ __('Clear') }}</span>
                            @endif
                        </td>
                        <td class="nowrap">
                            <span class="badge badge-{{ $tone }}">
                                @if ($left === null) {{ __('not set') }}
                                @elseif ($left < 0) {{ __(':days days overdue', ['days' => abs($left)]) }}
                                @else {{ __(':days days left', ['days' => $left]) }} @endif
                            </span>
                        </td>
                        <td class="faint">{{ __($app->districtOfficer?->name ?? 'Not assigned') }}</td>
                        <td class="text-end">
                            <a href="{{ route('approvals.show', $app) }}" class="btn btn-primary btn-sm">{{ __('Review') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($applications->hasPages())
            <div class="card-foot">{{ $applications->links() }}</div>
        @endif
    @endif
</div>

@endsection
