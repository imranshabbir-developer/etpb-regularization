@extends('layouts.print')

@section('doc-title', 'Consolidated report')
@section('doc-subject', ($districtName ? 'District ' . $districtName : 'All districts') . ' — consolidated / master report')

@section('doc-body')

@php
    $h = $headline;
    $p = $performance;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100) : 0;
@endphp

<h1>Consolidated report for higher authorities</h1>
<p class="muted">
    {{ $districtName ? 'District ' . $districtName : 'All districts' }} &mdash;
    Regularization of Possession, Clause 3(ii) of the Scheme 1977.
</p>

<h2>1. Headline</h2>

<table class="stats">
    <tr>
        <td>
            <span class="lbl">Applications received</span>
            <span class="val">{{ number_format($h['all']) }}</span>
            <span class="sub">{{ number_format($h['open']) }} open</span>
        </td>
        <td>
            <span class="lbl">Regularized</span>
            <span class="val">{{ number_format($h['regularized']) }}</span>
            <span class="sub">{{ $pct($h['regularized'], $h['all']) }}% of all received</span>
        </td>
        <td>
            <span class="lbl">Rejected</span>
            <span class="val">{{ number_format($h['rejected']) }}</span>
            <span class="sub">disposal rate {{ $h['disposal_rate'] }}%</span>
        </td>
        <td>
            <span class="lbl">Awaiting deposit</span>
            <span class="val">{{ number_format($h['pending_pay']) }}</span>
            <span class="sub">not processed</span>
        </td>
    </tr>
    <tr>
        <td>
            <span class="lbl">Monthly rent secured</span>
            <span class="val">{{ $rs($h['monthly_rent']) }}</span>
            <span class="sub">{{ $rs((float) $h['monthly_rent'] * 12) }} annualised</span>
        </td>
        <td>
            <span class="lbl">Arrears assessed</span>
            <span class="val">{{ $rs($h['assessed']) }}</span>
            <span class="sub">{{ $rs($h['recovered']) }} recovered</span>
        </td>
        <td>
            <span class="lbl">Arrears outstanding</span>
            <span class="val">{{ $rs($h['outstanding']) }}</span>
            <span class="sub">{{ $h['recovery_rate'] }}% recovered to date</span>
        </td>
        <td>
            <span class="lbl">Area regularized</span>
            <span class="val">{{ number_format((float) $h['area_sqft'], 0) }}</span>
            <span class="sub">sqft &middot; {{ number_format((float) $h['area_sqft'] / 5445, 2) }} Kanal</span>
        </td>
    </tr>
</table>

<h2>2. Fee collection</h2>

<table class="t">
    <thead>
    <tr><th>Measure</th><th class="num">Count</th><th class="num">Amount</th></tr>
    </thead>
    <tbody>
    <tr>
        <td>Deposits confirmed by Accounts</td>
        <td class="num">{{ number_format($h['fee_count']) }}</td>
        <td class="num">{{ $rs($h['fee_total']) }}</td>
    </tr>
    <tr>
        <td>Applications marked <strong>PAID</strong></td>
        <td class="num">{{ number_format($h['paid']) }}</td>
        <td class="num">&mdash;</td>
    </tr>
    <tr @if ($h['pending_pay'] > 0) class="hi" @endif>
        <td>Applications still <strong>PENDING</strong>, not processed</td>
        <td class="num">{{ number_format($h['pending_pay']) }}</td>
        <td class="num">&mdash;</td>
    </tr>
    </tbody>
</table>

<h2>3. Statutory deadlines</h2>

<table class="t">
    <thead>
    <tr>
        <th>Deadline</th><th class="num">In hand</th><th class="num">Overdue</th>
        <th class="num">On time</th><th>Authority</th>
    </tr>
    </thead>
    <tbody>
    <tr @if ($p['assessment_overdue'] > 0) class="hi" @endif>
        <td>Assessment within 60 days of first notice</td>
        <td class="num">{{ number_format($p['assessment_live']) }}</td>
        <td class="num">{{ number_format($p['assessment_overdue']) }}</td>
        <td class="num">{{ $p['assessment_ontime'] }}%</td>
        <td><span class="clause">Clause 10(i)(e)</span></td>
    </tr>
    <tr @if ($p['approval_overdue'] > 0) class="hi" @endif>
        <td>Administrator approval within one month</td>
        <td class="num">{{ number_format($p['approval_live']) }}</td>
        <td class="num">{{ number_format($p['approval_overdue']) }}</td>
        <td class="num">{{ $p['approval_ontime'] }}%</td>
        <td><span class="clause">Clause 3(ii)(d)</span></td>
    </tr>
    </tbody>
</table>

@php $allBreaches = collect($breaches['assessment'])->concat(collect($breaches['approval'])); @endphp

@if ($allBreaches->isNotEmpty())
    <h3>Cases past a deadline, with the officer answerable</h3>
    <table class="t">
        <thead>
        <tr>
            <th>Type</th><th>Application</th><th>Applicant</th><th>District</th>
            <th>Due</th><th class="num">Overdue</th><th>Officer</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($breaches['assessment'] as $a)
            @php $due = \Illuminate\Support\Carbon::parse($a->assessment_extended_to ?: $a->assessment_due_date); @endphp
            <tr>
                <td>Assessment</td>
                <td>{{ $a->application_no }}</td>
                <td>{{ $a->applicant?->full_name }}</td>
                <td>{{ $a->district?->name }}</td>
                <td>{{ $due->format('d-m-Y') }}</td>
                <td class="num">{{ (int) $due->diffInDays(now()) }} days</td>
                <td>{{ $a->districtOfficer?->name ?? 'Not assigned' }}</td>
            </tr>
        @endforeach
        @foreach ($breaches['approval'] as $a)
            @php $due = \Illuminate\Support\Carbon::parse($a->admin_approval_due_date); @endphp
            <tr>
                <td>Approval</td>
                <td>{{ $a->application_no }}</td>
                <td>{{ $a->applicant?->full_name }}</td>
                <td>{{ $a->district?->name }}</td>
                <td>{{ $due->format('d-m-Y') }}</td>
                <td class="num">{{ (int) $due->diffInDays(now()) }} days</td>
                <td>{{ $a->administrator?->name ?? 'Not assigned' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="note">No case is currently past a statutory deadline.</div>
@endif

<h2>4. By district</h2>

<table class="t">
    <thead>
    <tr>
        <th>District</th><th class="num">Received</th><th class="num">Paid</th>
        <th class="num">Regularized</th><th class="num">Sub judice</th>
        <th class="num">Assessed</th><th class="num">Recovered</th>
        <th class="num">Outstanding</th><th class="num">Recovery</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($byDistrict as $d)
        <tr>
            <td>{{ $d->district?->name ?? 'Not recorded' }}</td>
            <td class="num">{{ number_format($d->total) }}</td>
            <td class="num">{{ number_format($d->paid) }}</td>
            <td class="num">{{ number_format($d->regularized) }}</td>
            <td class="num">{{ number_format($d->sub_judice) }}</td>
            <td class="num">{{ number_format((float) $d->assessed, 0) }}</td>
            <td class="num">{{ number_format((float) $d->recovered, 0) }}</td>
            <td class="num">{{ number_format((float) $d->outstanding, 0) }}</td>
            <td class="num">{{ $pct((float) $d->recovered, (float) $d->assessed) }}%</td>
        </tr>
    @empty
        <tr><td colspan="9" class="muted">No applications yet.</td></tr>
    @endforelse
    </tbody>
</table>

<h2>5. Caseload by stage</h2>

<table class="t">
    <thead><tr><th>Stage</th><th class="num">Count</th><th class="num">Share</th></tr></thead>
    <tbody>
    @forelse ($headline['by_status'] as $status => $count)
        <tr>
            <td>{{ $labels[$status] ?? $status }}</td>
            <td class="num">{{ number_format($count) }}</td>
            <td class="num">{{ $pct($count, $h['all']) }}%</td>
        </tr>
    @empty
        <tr><td colspan="3" class="muted">No applications yet.</td></tr>
    @endforelse
    </tbody>
</table>

<h2>6. Objections</h2>

<table class="t">
    <thead><tr><th>Measure</th><th class="num">Count</th></tr></thead>
    <tbody>
    <tr><td>Filed</td><td class="num">{{ number_format($objections['filed']) }}</td></tr>
    <tr><td>Decided</td><td class="num">{{ number_format($objections['decided']) }}</td></tr>
    <tr @if ($objections['open'] > 0) class="hi" @endif>
        <td>Still open &mdash; rent cannot be fixed until decided</td>
        <td class="num">{{ number_format($objections['open']) }}</td>
    </tr>
    <tr><td>Filed after the 15-day window</td><td class="num">{{ number_format($objections['late']) }}</td></tr>
    @foreach ($objections['outcomes'] as $decision => $n)
        <tr>
            <td class="muted">&nbsp;&nbsp;{{ ucwords(strtolower(str_replace('_', ' ', $decision))) }}</td>
            <td class="num">{{ number_format($n) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>7. Intake, last twelve months</h2>

<table class="t">
    <thead>
    <tr><th>Month</th><th class="num">Applications submitted</th></tr>
    </thead>
    <tbody>
    @foreach ($monthly as $ym => $n)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</td>
            <td class="num">{{ number_format($n) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@endsection
