@extends('layouts.app')

@section('title', 'Arrears')
@section('heading', 'Arrears outstanding')

@section('content')

<div class="page-head">
    <h1>Arrears outstanding</h1>
    <p class="lede">
        Arrears must be cleared, spread over instalments, or remitted before approval.
        <span class="clause">Clause 3(ii)(b)</span>
    </p>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">Assessed</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->a, 0) }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">Recovered</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->p, 0) }}</div>
        <div class="tile-sub">
            {{ (float) $totals->a > 0 ? round((float) $totals->p / (float) $totals->a * 100) : 0 }}% of assessed
        </div>
    </div>
    <div class="tile is-danger">
        <div class="tile-label">Outstanding</div>
        <div class="tile-value">Rs. {{ number_format((float) $totals->b, 0) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ number_format($applications->total()) }} with a balance</h3>
        <div class="card-actions">
            <a href="{{ route('reports.registers', 'arrears') }}" class="btn btn-outline btn-sm">Statement</a>
        </div>
    </div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">Nothing outstanding.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>Application</th><th>Applicant</th><th>District</th>
                    <th class="num">Monthly rent</th><th class="num">Assessed</th>
                    <th class="num">Paid</th><th class="num">Balance</th><th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ $app->district?->name }}</td>
                        <td class="num">
                            {{ $app->assessed_monthly_rent ? number_format((float) $app->assessed_monthly_rent, 0) : '' }}
                        </td>
                        <td class="num">{{ number_format((float) $app->total_arrears, 0) }}</td>
                        <td class="num">{{ number_format((float) $app->arrears_paid, 0) }}</td>
                        <td class="num"><strong>{{ number_format((float) $app->arrears_balance, 0) }}</strong></td>
                        <td class="text-end">
                            <a href="{{ route('arrears.index', $app) }}" class="btn btn-outline btn-sm">Ledger</a>
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
