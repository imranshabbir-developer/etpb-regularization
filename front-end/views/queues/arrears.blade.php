@extends('layouts.app')

@section('title', __('Arrears'))
@section('heading', __('Arrears outstanding'))

@section('content')

<div class="page-head">
    <h1>{{ __('Arrears outstanding') }}</h1>
    <p class="lede">
        {{ __('Arrears must be cleared, spread over instalments, or remitted before approval.') }}
        <span class="clause">{{ __('Clause 3(ii)(b)') }}</span>
    </p>
</div>

@php
    $recoveryChart = [
        'labels' => [__('Recovered'), __('Outstanding')],
        'values' => [(float) $totals->p, (float) $totals->b],
        'colors' => ['#2F66B0', '#E8891A'],
        'centerLabel' => __('Arrears'),
        'centerValue' => ((float) $totals->a > 0
            ? round((float) $totals->p / (float) $totals->a * 100) . '%'
            : '0%'),
    ];
@endphp

<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('Assessed') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->a, 0) }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Recovered') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->p, 0) }}</div>
        <div class="tile-sub">
            {{ __(':pct% of assessed', ['pct' => (float) $totals->a > 0 ? round((float) $totals->p / (float) $totals->a * 100) : 0]) }}
        </div>
    </div>
    <div class="tile is-danger">
        <div class="tile-label">{{ __('Outstanding') }}</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->b, 0) }}</div>
    </div>
</div>

<div class="card mb-[1.15rem]">
    <div class="card-head"><h3>{{ __('Recovery split') }}</h3></div>
    <div class="card-body chart-card-body">
        <div class="chart-frame is-sm">
            <canvas data-etpb-chart="doughnut" data-etpb-source="arrears-recovery-data"
                    aria-label="{{ __('Recovered versus outstanding arrears') }}"></canvas>
        </div>
        <script type="application/json" id="arrears-recovery-data">@json($recoveryChart)</script>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ __(':count with a balance', ['count' => number_format($applications->total())]) }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.registers', 'arrears') }}" class="btn btn-outline btn-sm">{{ __('Statement') }}</a>
        </div>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">{{ __('Nothing outstanding.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('District') }}</th>
                    <th class="num">{{ __('Monthly rent') }}</th><th class="num">{{ __('Assessed') }}</th>
                    <th class="num">{{ __('Paid') }}</th><th class="num">{{ __('Balance') }}</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ __($app->district?->name ?? '') }}</td>
                        <td class="num">
                            {{ $app->assessed_monthly_rent ? number_format((float) $app->assessed_monthly_rent, 0) : '' }}
                        </td>
                        <td class="num">{{ number_format((float) $app->total_arrears, 0) }}</td>
                        <td class="num">{{ number_format((float) $app->arrears_paid, 0) }}</td>
                        <td class="num"><strong>{{ number_format((float) $app->arrears_balance, 0) }}</strong></td>
                        <td class="text-end">
                            <a href="{{ route('arrears.index', $app) }}" class="btn btn-outline btn-sm">{{ __('Ledger') }}</a>
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
