@extends('layouts.app')

@section('title', 'Performance at a glance')
@section('heading', 'Performance at a glance')

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
    $short = function ($n) {
        $n = (float) $n;
        if ($n >= 10000000) return 'Rs. ' . number_format($n / 10000000, 2) . ' cr';
        if ($n >= 100000)   return 'Rs. ' . number_format($n / 100000, 2) . ' lac';
        return 'Rs. ' . number_format($n, 0);
    };
@endphp

<div class="page-head">
    <h1>Performance at a glance</h1>
    <p class="lede">
        {{ $districtName ? 'District ' . $districtName : 'All districts' }} &middot;
        as at {{ $generatedAt->format('d F Y') }}
    </p>
</div>

<div class="card no-print">
    <div class="card-body tight">
        <form method="GET" action="{{ route('reports.glimpse') }}"
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
            <noscript><button class="btn btn-primary btn-sm" type="submit">Apply</button></noscript>
        </form>
        <div class="mt-2">
            @include('partials.report-formats', [
                'route' => route('reports.glimpse'),
                'query' => array_filter(['district' => $districtId]),
            ])
        </div>
    </div>
</div>

{{-- ---------- The four numbers that matter ---------- --}}
<div class="tiles">
    <div class="tile">
        <div class="tile-label">Applications</div>
        <div class="tile-value">{{ number_format($h['all']) }}</div>
        <div class="tile-sub">{{ number_format($h['open']) }} open &middot; {{ $h['disposal_rate'] }}% disposed</div>
    </div>
    <div class="tile">
        <div class="tile-label">Regularized</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">
            {{ number_format((float) $h['area_sqft'] / 5445, 1) }} Kanal brought on record
        </div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">Rent secured</div>
        <div class="tile-value">{{ $short((float) $h['monthly_rent'] * 12) }}</div>
        <div class="tile-sub">a year &middot; {{ $rs($h['monthly_rent']) }} a month</div>
    </div>
    <div class="tile {{ $h['recovery_rate'] < 50 ? 'is-warn' : '' }}">
        <div class="tile-label">Arrears recovered</div>
        <div class="tile-value">{{ $h['recovery_rate'] }}%</div>
        <div class="tile-sub">{{ $short($h['outstanding']) }} still outstanding</div>
    </div>
</div>

{{-- ---------- Deadline performance ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>Are the statutory deadlines being met?</h3>
        @if ($p['assessment_overdue'] + $p['approval_overdue'] > 0)
            <span class="badge badge-danger">{{ $p['assessment_overdue'] + $p['approval_overdue'] }} overdue</span>
        @else
            <span class="badge badge-good">All on time</span>
        @endif
    </div>
    <div class="card-body">
        <div class="grid-2 gap-y-4">
            @foreach ([
                ['Assessment within 60 days', $p['assessment_ontime'], $p['assessment_live'], $p['assessment_overdue'], 'Clause 10(i)(e)'],
                ['Approval within one month', $p['approval_ontime'], $p['approval_live'], $p['approval_overdue'], 'Clause 3(ii)(d)'],
            ] as [$label, $pct, $live, $over, $clause])
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <strong class="text-[.9rem]">{{ $label }}</strong>
                        <span class="clause">{{ $clause }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="text-2xl font-bold tabular-nums
                                    {{ $pct >= 90 ? 'text-pk-700' : ($pct >= 70 ? 'text-warn-700' : 'text-danger-600') }}">
                            {{ $pct }}%
                        </div>
                        <div class="sla-bar flex-1">
                            <div class="sla-fill {{ $pct >= 90 ? '' : ($pct >= 70 ? 'is-warn' : 'is-danger') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    <div class="tile-sub">
                        {{ number_format($live) }} in hand
                        @if ($over > 0) &middot; <strong class="text-danger-600">{{ number_format($over) }} overdue</strong> @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($p['avg_days'] !== null)
            <hr class="divider">
            <p class="mb-0 muted text-[.88rem]">
                A case takes on average <strong>{{ number_format($p['avg_days']) }} days</strong>
                from submission to regularization.
                Of {{ number_format($p['approvals_total']) }} approvals decided so far,
                <strong>{{ $p['approvals_pct'] }}%</strong> were within the month.
            </p>
        @endif
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    {{-- ---------- Where the work is ---------- --}}
    <div class="card">
        <div class="card-head"><h3>Busiest districts</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>District</th><th class="num">Cases</th><th class="num">Done</th><th class="num">Recovery</th></tr>
                </thead>
                <tbody>
                @forelse ($byDistrict->take(8) as $d)
                    @php $rec = (float) $d->assessed > 0 ? round((float) $d->recovered / (float) $d->assessed * 100) : 0; @endphp
                    <tr>
                        <td>{{ $d->district?->name ?? 'Not recorded' }}</td>
                        <td class="num">{{ number_format($d->total) }}</td>
                        <td class="num">{{ number_format($d->regularized) }}</td>
                        <td class="num">
                            <span class="badge badge-{{ $rec >= 60 ? 'good' : ($rec >= 25 ? 'warn' : 'neutral') }}">
                                {{ $rec }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="row-muted">No applications yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---------- Attention ---------- --}}
    <div class="card">
        <div class="card-head"><h3>Needs attention</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>Awaiting the Rs. 5,000 deposit</dt>
                <dd>
                    <span class="badge badge-{{ $h['pending_pay'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($h['pending_pay']) }}
                    </span>
                    <span class="faint text-[.8rem]">not processed until paid</span>
                </dd>

                <dt>Sub judice</dt>
                <dd>
                    <span class="badge badge-{{ $h['sub_judice'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($h['sub_judice']) }}
                    </span>
                    <span class="faint text-[.8rem]">before a court, or stayed</span>
                </dd>

                <dt>Objections still open</dt>
                <dd>
                    <span class="badge badge-{{ $objections['open'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($objections['open']) }}
                    </span>
                    <span class="faint text-[.8rem]">rent cannot be fixed until decided</span>
                </dd>

                <dt>Fee collected</dt>
                <dd>{{ $rs($h['fee_total']) }}
                    <span class="faint text-[.8rem]">({{ number_format($h['fee_count']) }} instruments)</span></dd>
            </dl>

            @can('do', 'reports.executive')
                <hr class="divider">
                <a href="{{ route('reports.executive', array_filter(['district' => $districtId])) }}"
                   class="btn btn-primary btn-sm">
                    Full consolidated report &rarr;
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- ---------- Intake trend ---------- --}}
<div class="card">
    <div class="card-head"><h3>Applications submitted, last 12 months</h3></div>
    <div class="card-body">
        @php $max = max($monthly->values()->all() ?: [1]); @endphp
        <div class="flex items-end gap-1 sm:gap-2 h-[140px]">
            @foreach ($monthly as $ym => $n)
                <div class="flex-1 flex flex-col items-center gap-1 h-full min-w-0">
                    <div class="text-[.7rem] tabular-nums muted">{{ $n }}</div>
                    <div class="w-full bg-pk-600 rounded-t mt-auto"
                         style="height:{{ $max > 0 ? max(3, round($n / $max * 100)) : 3 }}%"
                         title="{{ $n }} in {{ $ym }}"></div>
                    <div class="faint text-[.62rem] sm:text-[.68rem] whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('M') }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
