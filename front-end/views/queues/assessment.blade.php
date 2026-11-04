@extends('layouts.app')

@section('title', __('Assessment queue'))
@section('heading', __('Rent assessment queue'))

@section('content')

<div class="page-head">
    <h1>{{ __('Assessment') }}</h1>
    <p class="lede">
        {{ __('Ordered by how close the sixty-day limit is.') }} <span class="clause">{{ __('Clause 10(i)(e)') }}</span>
    </p>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('In assessment') }}</div>
        <div class="tile-value">{{ number_format($stats['in_queue'] ?? $applications->total()) }}</div>
        <div class="tile-sub">{{ __('Active assessment stages') }}</div>
    </div>
    <div class="tile {{ ($stats['due_soon'] ?? 0) > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Due within 7 days') }}</div>
        <div class="tile-value">{{ number_format($stats['due_soon'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Sixty-day clock closing soon') }}</div>
    </div>
    <div class="tile {{ ($stats['overdue'] ?? 0) > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">{{ __('Past due') }}</div>
        <div class="tile-value">{{ number_format($stats['overdue'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Assessment SLA breached') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>{{ __(':count in assessment', ['count' => number_format($applications->total())]) }}</h3></div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'scale'])
            <p class="mb-0">{{ __('Nothing in assessment.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('District') }}</th><th>{{ __('Stage') }}</th><th>{{ __('Assessment due') }}</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    @php $sla = $app->assessmentSla(); @endphp
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ __($app->district?->name ?? '') }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                        <td class="nowrap">
                            @if ($sla['applies'])
                                <span class="badge badge-{{ $sla['tone'] }}">
                                    {{ $sla['days_left'] < 0
                                        ? __('Assessment overdue by :days day(s)', ['days' => abs($sla['days_left'])])
                                        : __('Assessment due in :days day(s)', ['days' => $sla['days_left']]) }}
                                </span>
                            @else
                                <span class="faint">{{ __('not started') }}</span>
                            @endif
                        </td>
                        <td class="text-end nowrap">
                            <a href="{{ route('assessment.show', $app) }}" class="btn btn-outline btn-sm">{{ __('Assess') }}</a>
                            <a href="{{ route('due-process.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Notices') }}</a>
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
