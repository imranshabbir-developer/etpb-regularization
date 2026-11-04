@extends('layouts.app')

@section('title', __('Scrutiny queue'))
@section('heading', __('Scrutiny queue'))

@section('content')

<div class="page-head">
    <h1>{{ __('Scrutiny') }}</h1>
    <p class="lede">{{ __('Applications ready to be examined, and deposits still waiting on Accounts.') }}</p>
</div>

<div class="tiles">
    <div class="tile {{ ($stats['deposits'] ?? 0) > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Deposits to confirm') }}</div>
        <div class="tile-value">{{ number_format($stats['deposits'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Rs. 5,000 still pending with Accounts') }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Ready for scrutiny') }}</div>
        <div class="tile-value">{{ number_format($stats['scrutiny'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Paid files waiting examination') }}</div>
    </div>
</div>

@if ($awaitingPayment->isNotEmpty())
    <div class="card">
        <div class="card-head">
            <h3>{{ __('Awaiting the Rs. 5,000 deposit') }}</h3>
            <span class="badge badge-warn">{{ $awaitingPayment->count() }}</span>
        </div>
        <div class="card-body tight">
            <p class="hint" style="margin-top:0">
                {{ __('These are not processed. They appear here only so Accounts can see what is outstanding.') }}
            </p>
        </div>
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('District') }}</th><th>{{ __('Instrument') }}</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($awaitingPayment as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ __($app->district?->name ?? '') }}</td>
                        <td>
                            @if ($app->feePayments->isEmpty())
                                <span class="badge badge-neutral">{{ __('Not recorded') }}</span>
                            @else
                                <span class="badge badge-info">{{ __('Recorded, unconfirmed') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('fee.index', $app) }}" class="btn btn-outline btn-sm">{{ __('Fee') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-head">
        <h3>{{ __('Ready for scrutiny') }}</h3>
        <span class="badge badge-neutral">{{ number_format($applications->total()) }}</span>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">{{ __('Nothing waiting.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>{{ __('Application') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('Property') }}</th><th>{{ __('District') }}</th><th>{{ __('Stage') }}</th><th>{{ __('Submitted') }}</th></tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td class="nowrap">{{ $app->property?->property_no }}</td>
                        <td>{{ __($app->district?->name ?? '') }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                        <td class="nowrap faint">{{ $app->submitted_at?->diffForHumans() ?? __('not submitted') }}</td>
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
