@extends('layouts.app')

@section('title', 'Consolidated report')
@section('heading', 'Consolidated / master report')

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100) : 0;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
    $allBreaches = collect($breaches['assessment'])->concat(collect($breaches['approval']));
@endphp

<div class="page-head">
    <h1>Consolidated report</h1>
    <p class="lede">
        Evacuee Trust Property Board &middot;
        {{ $districtName ? 'District ' . $districtName : 'All districts' }} &middot;
        as at {{ $generatedAt->format('d F Y, H:i') }}
    </p>
</div>

<div class="card no-print">
    <div class="card-body tight">
        <form method="GET" action="{{ route('reports.executive') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="field mb-0 min-w-[200px]">
                <label for="district">District</label>
                <select id="district" name="district" class="select" onchange="this.form.submit()">
                    <option value="">All districts</option>
                    @foreach ($districts as $d)
                        <option value="{{ $d->id }}" @selected($districtId === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <a class="btn btn-ghost btn-sm" href="{{ route('reports.glimpse') }}">At a glance</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('reports.registers') }}">Registers</a>
        </form>
        <div class="mt-2">
            @include('partials.report-formats', [
                'route' => route('reports.executive'),
                'query' => array_filter(['district' => $districtId]),
            ])
        </div>
    </div>
</div>

{{-- ---------- Headline ---------- --}}
<div class="tiles">
    <div class="tile">
        <div class="tile-label">Applications received</div>
        <div class="tile-value">{{ number_format($h['all']) }}</div>
        <div class="tile-sub">{{ number_format($h['open']) }} still open</div>
    </div>
    <div class="tile">
        <div class="tile-label">Regularized</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">{{ $pct($h['regularized'], $h['all']) }}% of all received</div>
    </div>
    <div class="tile {{ $h['pending_pay'] > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">Awaiting deposit</div>
        <div class="tile-value">{{ number_format($h['pending_pay']) }}</div>
        <div class="tile-sub">not processed until paid</div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">Fee collected</div>
        <div class="tile-value">{{ $rs($h['fee_total']) }}</div>
        <div class="tile-sub">{{ number_format($h['fee_count']) }} instruments confirmed</div>
    </div>
    <div class="tile">
        <div class="tile-label">Monthly rent secured</div>
        <div class="tile-value">{{ $rs($h['monthly_rent']) }}</div>
        <div class="tile-sub">{{ $rs((float) $h['monthly_rent'] * 12) }} annualised</div>
    </div>
    <div class="tile {{ (float) $h['outstanding'] > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">Arrears outstanding</div>
        <div class="tile-value">{{ $rs($h['outstanding']) }}</div>
        <div class="tile-sub">
            of {{ $rs($h['assessed']) }} assessed &middot; {{ $h['recovery_rate'] }}% recovered
        </div>
    </div>
    <div class="tile {{ $h['sub_judice'] > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">Sub judice</div>
        <div class="tile-value">{{ number_format($h['sub_judice']) }}</div>
        <div class="tile-sub">pending before a court or stayed</div>
    </div>
    <div class="tile">
        <div class="tile-label">Area regularized</div>
        <div class="tile-value">{{ number_format((float) $h['area_sqft'], 0) }}</div>
        <div class="tile-sub">
            sqft &middot; about {{ number_format((float) $h['area_sqft'] / 5445, 2) }} Kanal
        </div>
    </div>
</div>

{{-- ---------- Statutory deadlines ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>Statutory deadlines breached</h3>
        <span class="badge badge-{{ $allBreaches->count() ? 'danger' : 'good' }}">
            {{ $allBreaches->count() }}
        </span>
        <div class="card-actions">
            <span class="clause">Clause 10(i)(e)</span>
            <span class="clause">Clause 3(ii)(d)</span>
        </div>
    </div>

    @if ($allBreaches->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">No statutory deadline is currently breached.</p>
        </div>
    @else
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr>
                    <th>Application</th><th>Applicant</th><th>District</th>
                    <th>Deadline</th><th>Overdue by</th><th>Officer answerable</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($breaches['assessment'] as $a)
                    @php $due = \Illuminate\Support\Carbon::parse($a->assessment_extended_to ?: $a->assessment_due_date); @endphp
                    <tr>
                        <td class="nowrap"><a href="{{ route('applications.show', $a) }}">{{ $a->application_no }}</a></td>
                        <td>{{ $a->applicant?->full_name }}</td>
                        <td>{{ $a->district?->name }}</td>
                        <td class="nowrap">Assessment &mdash; {{ $due->format('d-m-Y') }}</td>
                        <td class="nowrap">
                            <span class="badge badge-danger">{{ (int) $due->diffInDays(now()) }} days</span>
                        </td>
                        <td class="{{ $a->districtOfficer ? '' : 'row-muted' }}">
                            {{ $a->districtOfficer?->name ?? 'Not assigned' }}
                        </td>
                    </tr>
                @endforeach
                @foreach ($breaches['approval'] as $a)
                    @php $due = \Illuminate\Support\Carbon::parse($a->admin_approval_due_date); @endphp
                    <tr>
                        <td class="nowrap"><a href="{{ route('applications.show', $a) }}">{{ $a->application_no }}</a></td>
                        <td>{{ $a->applicant?->full_name }}</td>
                        <td>{{ $a->district?->name }}</td>
                        <td class="nowrap">Approval &mdash; {{ $due->format('d-m-Y') }}</td>
                        <td class="nowrap">
                            <span class="badge badge-danger">{{ (int) $due->diffInDays(now()) }} days</span>
                        </td>
                        <td class="{{ $a->administrator ? '' : 'row-muted' }}">
                            {{ $a->administrator?->name ?? 'Not assigned' }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ---------- Deadline performance ---------- --}}
<div class="card">
    <div class="card-head"><h3>Deadline performance</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>Deadline</th><th class="num">In hand</th><th class="num">Overdue</th>
                <th class="num">On time</th><th>Authority</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>Assessment within 60 days of first notice</td>
                <td class="num">{{ number_format($p['assessment_live']) }}</td>
                <td class="num">{{ number_format($p['assessment_overdue']) }}</td>
                <td class="num"><strong>{{ $p['assessment_ontime'] }}%</strong></td>
                <td><span class="clause">Clause 10(i)(e)</span></td>
            </tr>
            <tr>
                <td>Administrator approval within one month</td>
                <td class="num">{{ number_format($p['approval_live']) }}</td>
                <td class="num">{{ number_format($p['approval_overdue']) }}</td>
                <td class="num"><strong>{{ $p['approval_ontime'] }}%</strong></td>
                <td><span class="clause">Clause 3(ii)(d)</span></td>
            </tr>
            <tr>
                <td>Approvals decided within the month, to date</td>
                <td class="num">{{ number_format($p['approvals_total']) }}</td>
                <td class="num">{{ number_format($p['approvals_total'] - $p['approvals_in_time']) }}</td>
                <td class="num"><strong>{{ $p['approvals_pct'] }}%</strong></td>
                <td><span class="clause">Clause 3(ii)(d)</span></td>
            </tr>
            </tbody>
        </table>
    </div>
    @if ($p['avg_days'] !== null)
        <div class="card-foot">
            <p class="mb-0 faint text-[.8rem]">
                A case takes on average {{ number_format($p['avg_days']) }} days from submission
                to regularization.
            </p>
        </div>
    @endif
</div>

{{-- ---------- District league table ---------- --}}
<div class="card">
    <div class="card-head"><h3>By district</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr>
                <th>District</th>
                <th class="num">Received</th><th class="num">Paid</th><th class="num">Regularized</th>
                <th class="num">Sub judice</th>
                <th class="num">Assessed</th><th class="num">Recovered</th><th class="num">Outstanding</th>
                <th class="num">Recovery</th>
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
                    <td class="num"><strong>{{ number_format((float) $d->outstanding, 0) }}</strong></td>
                    <td class="num">{{ $pct((float) $d->recovered, (float) $d->assessed) }}%</td>
                </tr>
            @empty
                <tr><td colspan="9" class="row-muted">No applications yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    {{-- ---------- Caseload by stage ---------- --}}
    <div class="card">
        <div class="card-head"><h3>Caseload by stage</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>Stage</th><th class="num">Count</th><th class="num">Share</th></tr></thead>
                <tbody>
                @forelse ($h['by_status'] as $status => $count)
                    <tr>
                        <td>
                            <span class="badge badge-{{ \App\Services\WorkflowService::TONES[$status] ?? 'neutral' }}">
                                {{ $labels[$status] ?? $status }}
                            </span>
                        </td>
                        <td class="num">{{ number_format($count) }}</td>
                        <td class="num">{{ $pct($count, $h['all']) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="row-muted">No applications yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---------- Objections ---------- --}}
    <div class="card">
        <div class="card-head">
            <h3>Objections</h3>
            <div class="card-actions"><span class="clause">Clause 10(i)(c)–(d)</span></div>
        </div>
        <div class="card-body">
            <dl class="kv">
                <dt>Filed</dt><dd>{{ number_format($objections['filed']) }}</dd>
                <dt>Decided</dt><dd>{{ number_format($objections['decided']) }}</dd>
                <dt>Still open</dt><dd><strong>{{ number_format($objections['open']) }}</strong></dd>
                <dt>Filed out of time</dt><dd>{{ number_format($objections['late']) }}</dd>
            </dl>
            @if ($objections['outcomes']->isNotEmpty())
                <hr class="divider">
                <div class="inline-list">
                    @foreach ($objections['outcomes'] as $decision => $n)
                        <span class="badge badge-{{ $decision === 'ACCEPTED' ? 'good' : ($decision === 'REJECTED' ? 'danger' : 'warn') }}">
                            {{ ucwords(strtolower(str_replace('_', ' ', $decision))) }} <strong>{{ $n }}</strong>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ---------- Intake trend ---------- --}}
<div class="card">
    <div class="card-head"><h3>Applications submitted, last 12 months</h3></div>
    <div class="card-body">
        @php $max = max($monthly->values()->all() ?: [1]); @endphp
        <div class="flex items-end gap-1 sm:gap-2 h-[150px]">
            @foreach ($monthly as $ym => $n)
                <div class="flex-1 flex flex-col items-center gap-1 h-full min-w-0">
                    <div class="text-[.72rem] tabular-nums muted">{{ $n }}</div>
                    <div class="w-full bg-pk-600 rounded-t mt-auto"
                         style="height:{{ $max > 0 ? max(3, round($n / $max * 100)) : 3 }}%"></div>
                    <div class="faint text-[.62rem] sm:text-[.68rem] whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('M y') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
