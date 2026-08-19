@extends('layouts.app')

@section('title', 'Scrutiny queue')
@section('heading', 'Scrutiny queue')

@section('content')

<div class="page-head">
    <h1>Scrutiny</h1>
    <p class="lede">Applications ready to be examined, and deposits still waiting on Accounts.</p>
</div>

@if ($awaitingPayment->isNotEmpty())
    <div class="card">
        <div class="card-head">
            <h3>Awaiting the Rs. 5,000 deposit</h3>
            <span class="badge badge-warn">{{ $awaitingPayment->count() }}</span>
        </div>
        <div class="card-body tight">
            <p class="hint" style="margin-top:0">
                These are not processed. They appear here only so Accounts can see what is outstanding.
            </p>
        </div>
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>Application</th><th>Applicant</th><th>District</th><th>Instrument</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($awaitingPayment as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ $app->district?->name }}</td>
                        <td>
                            @if ($app->feePayments->isEmpty())
                                <span class="badge badge-neutral">Not recorded</span>
                            @else
                                <span class="badge badge-info">Recorded, unconfirmed</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('fee.index', $app) }}" class="btn btn-outline btn-sm">Fee</a>
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
        <h3>Ready for scrutiny</h3>
        <span class="badge badge-neutral">{{ number_format($applications->total()) }}</span>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">Nothing waiting.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>Application</th><th>Applicant</th><th>Property</th><th>District</th><th>Stage</th><th>Submitted</th></tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td class="nowrap">{{ $app->property?->property_no }}</td>
                        <td>{{ $app->district?->name }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                        <td class="nowrap faint">{{ $app->submitted_at?->diffForHumans() ?? 'not submitted' }}</td>
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
