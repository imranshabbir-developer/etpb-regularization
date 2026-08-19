@extends('layouts.print')

@section('doc-title', 'Performance at a glance')
@section('doc-subject',
    'Performance of the Regularization of Possession Scheme — ' .
    ($districtName ? 'District ' . $districtName : 'All Districts'))

@section('doc-body')

@php
    $h = $headline;
    $p = $performance;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
@endphp

<p>
    The position of the scheme as at <strong>{{ $generatedAt->format('d F Y') }}</strong> is
    summarised below for the information of the competent authority. Detailed case-wise
    particulars are available in the consolidated report.
</p>

<h2>1. Position at a glance</h2>

<table class="t">
    <colgroup>
        <col style="width:7%" /><col style="width:40%" /><col style="width:19%" /><col style="width:34%" />
    </colgroup>
    <thead>
    <tr>
        <th class="sr">Sr.</th>
        <th>Particulars</th>
        <th class="num">Number / Amount</th>
        <th>Remarks</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="sr">1</td>
        <td>Applications received</td>
        <td class="num">{{ number_format($h['all']) }}</td>
        <td>{{ number_format($h['open']) }} under process</td>
    </tr>
    <tr>
        <td class="sr">2</td>
        <td>Cases regularized</td>
        <td class="num">{{ number_format($h['regularized']) }}</td>
        <td>{{ $h['disposal_rate'] }}% of all cases disposed of</td>
    </tr>
    <tr>
        <td class="sr">3</td>
        <td>Applications rejected</td>
        <td class="num">{{ number_format($h['rejected']) }}</td>
        <td>ineligible or refused on merits</td>
    </tr>
    <tr @if ($h['pending_pay'] > 0) class="hi" @endif>
        <td class="sr">4</td>
        <td>Awaiting the prescribed deposit</td>
        <td class="num">{{ number_format($h['pending_pay']) }}</td>
        <td>not taken up for process until paid</td>
    </tr>
    <tr>
        <td class="sr">5</td>
        <td>Sub judice</td>
        <td class="num">{{ number_format($h['sub_judice']) }}</td>
        <td>pending before a court, or stayed</td>
    </tr>
    <tr>
        <td class="sr">6</td>
        <td>Monthly rent secured (Rs.)</td>
        <td class="num">{{ number_format((float) $h['monthly_rent'], 0) }}</td>
        <td>{{ $rs((float) $h['monthly_rent'] * 12) }} per annum</td>
    </tr>
    <tr>
        <td class="sr">7</td>
        <td>Arrears assessed (Rs.)</td>
        <td class="num">{{ number_format((float) $h['assessed'], 0) }}</td>
        <td>under Clause 3(ii)(b)</td>
    </tr>
    <tr>
        <td class="sr">8</td>
        <td>Arrears recovered (Rs.)</td>
        <td class="num">{{ number_format((float) $h['recovered'], 0) }}</td>
        <td>{{ $h['recovery_rate'] }}% of the amount assessed</td>
    </tr>
    <tr>
        <td class="sr">9</td>
        <td>Arrears outstanding (Rs.)</td>
        <td class="num">{{ number_format((float) $h['outstanding'], 0) }}</td>
        <td>recoverable</td>
    </tr>
    <tr>
        <td class="sr">10</td>
        <td>Processing fee realised (Rs.)</td>
        <td class="num">{{ number_format((float) $h['fee_total'], 0) }}</td>
        <td>{{ number_format($h['fee_count']) }} instruments confirmed</td>
    </tr>
    <tr>
        <td class="sr">11</td>
        <td>Area regularized (square feet)</td>
        <td class="num">{{ number_format((float) $h['area_sqft'], 0) }}</td>
        <td>about {{ number_format((float) $h['area_sqft'] / 5445, 2) }} Kanal</td>
    </tr>
    </tbody>
</table>

<div class="keep">
<h2>2. Observance of statutory time limits</h2>

<table class="t">
    <colgroup>
        <col style="width:6%" /><col style="width:38%" /><col style="width:13%" />
        <col style="width:13%" /><col style="width:14%" /><col style="width:16%" />
    </colgroup>
    <thead>
    <tr>
        <th class="sr">Sr.</th>
        <th>Time limit prescribed</th>
        <th class="num">In&nbsp;hand</th>
        <th class="num">Overdue</th>
        <th class="num">Within&nbsp;time</th>
        <th>Authority</th>
    </tr>
    </thead>
    <tbody>
    <tr @if ($p['assessment_overdue'] > 0) class="hi" @endif>
        <td class="sr">1</td>
        <td>Assessment of rent within sixty days of the first notice</td>
        <td class="num">{{ number_format($p['assessment_live']) }}</td>
        <td class="num">{{ number_format($p['assessment_overdue']) }}</td>
        <td class="num"><strong>{{ $p['assessment_ontime'] }}%</strong></td>
        <td class="clause">Clause 10(i)(e)</td>
    </tr>
    <tr @if ($p['approval_overdue'] > 0) class="hi" @endif>
        <td class="sr">2</td>
        <td>Approval by the Administrator within one month</td>
        <td class="num">{{ number_format($p['approval_live']) }}</td>
        <td class="num">{{ number_format($p['approval_overdue']) }}</td>
        <td class="num"><strong>{{ $p['approval_ontime'] }}%</strong></td>
        <td class="clause">Clause 3(ii)(d)</td>
    </tr>
    <tr>
        <td class="sr">3</td>
        <td>Approvals decided within the month, cumulative to date</td>
        <td class="num">{{ number_format($p['approvals_total']) }}</td>
        <td class="num">{{ number_format($p['approvals_total'] - $p['approvals_in_time']) }}</td>
        <td class="num"><strong>{{ $p['approvals_pct'] }}%</strong></td>
        <td class="clause">Clause 3(ii)(d)</td>
    </tr>
    </tbody>
</table>
</div>

@if ($p['avg_days'] !== null && $p['avg_days'] >= 1)
    <div class="note">
        A case takes on average <strong>{{ number_format($p['avg_days']) }} days</strong> from
        submission to regularization.
    </div>
@endif

@if ($p['assessment_overdue'] > 0 || $p['approval_overdue'] > 0)
    <div class="danger">
        <strong>{{ number_format($p['assessment_overdue'] + $p['approval_overdue']) }} case(s) have
        exceeded a statutory time limit.</strong>
        These are listed case-wise, with the officer answerable, in the consolidated report.
    </div>
@else
    <div class="note">No case has exceeded a statutory time limit as at the date of this report.</div>
@endif

<h2>3. District-wise position</h2>

<table class="t">
    <colgroup>
        <col style="width:7%" /><col style="width:27%" /><col style="width:12%" /><col style="width:11%" />
        <col style="width:14%" /><col style="width:18%" /><col style="width:11%" />
    </colgroup>
    <thead>
    <tr>
        <th class="sr">Sr.</th>
        <th>District</th>
        <th class="num">Received</th>
        <th class="num">Paid</th>
        <th class="num">Regularized</th>
        <th class="num">Arrears outstanding (Rs.)</th>
        <th class="num">Recovery</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($byDistrict->take(12) as $d)
        <tr>
            <td class="sr">{{ $loop->iteration }}</td>
            <td>{{ $d->district?->name ?? 'Not recorded' }}</td>
            <td class="num">{{ number_format($d->total) }}</td>
            <td class="num">{{ number_format($d->paid) }}</td>
            <td class="num">{{ number_format($d->regularized) }}</td>
            <td class="num">{{ number_format((float) $d->outstanding, 0) }}</td>
            <td class="num">
                {{ (float) $d->assessed > 0 ? round((float) $d->recovered / (float) $d->assessed * 100) : 0 }}%
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="c muted">No applications have been received.</td></tr>
    @endforelse
    </tbody>
</table>

@if ($byDistrict->count() > 12)
    <p class="faint">The twelve busiest districts of {{ $byDistrict->count() }} are shown.</p>
@endif

<h2>4. Applications received, last twelve months</h2>

<table class="t">
    <thead>
    <tr>
        <th class="sr">Sr.</th>
        <th>Month</th>
        <th class="num">Applications submitted</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($monthly as $ym => $n)
        <tr>
            <td class="sr">{{ $loop->iteration }}</td>
            <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</td>
            <td class="num">{{ number_format($n) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2">Total</td>
        <td class="num">{{ number_format($monthly->sum()) }}</td>
    </tr>
    </tfoot>
</table>

<div class="note">
    <strong>Objections:</strong> {{ number_format($objections['filed']) }} filed,
    {{ number_format($objections['decided']) }} decided,
    {{ number_format($objections['open']) }} pending. Under Clause 10(i)(d) the rent cannot be
    fixed while an objection remains undecided.
</div>

<p>Submitted for the kind perusal of the competent authority.</p>

@endsection
