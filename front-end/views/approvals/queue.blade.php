@extends('layouts.app')

@section('title', 'Approval queue')
@section('heading', 'Pending approval')

@section('content')

<div class="page-head">
    <h1>Applications awaiting approval</h1>
    <p class="lede">
        Regularization is approved by the Administrator within one month of the decision,
        after recording reasons. <span class="clause">Clause 3(ii)(d)</span>
    </p>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ number_format($applications->total()) }} awaiting decision</h3>
        <div class="card-actions">
            <a href="{{ route('reports.executive') }}" class="btn btn-outline btn-sm">Executive report</a>
        </div>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">Nothing is waiting on a decision.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>Application</th><th>Applicant</th><th>District</th>
                    <th class="num">Rent fixed</th><th class="num">Arrears balance</th>
                    <th>Due by</th><th>District Officer</th><th></th>
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
                        <td>{{ $app->district?->name }}</td>
                        <td class="num">
                            {{ $app->assessed_monthly_rent ? 'Rs. ' . number_format((float) $app->assessed_monthly_rent, 0) : '—' }}
                        </td>
                        <td class="num">
                            @if ((float) $app->arrears_balance > 0)
                                <strong>Rs. {{ number_format((float) $app->arrears_balance, 0) }}</strong>
                            @else
                                <span class="badge badge-good">Clear</span>
                            @endif
                        </td>
                        <td class="nowrap">
                            <span class="badge badge-{{ $tone }}">
                                @if ($left === null) not set
                                @elseif ($left < 0) {{ abs($left) }} days overdue
                                @else {{ $left }} days left @endif
                            </span>
                        </td>
                        <td class="faint">{{ $app->districtOfficer?->name ?? 'Not assigned' }}</td>
                        <td class="text-end">
                            <a href="{{ route('approvals.show', $app) }}" class="btn btn-primary btn-sm">Review</a>
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
