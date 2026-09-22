@extends('layouts.app')

@section('title', 'Overview')
@section('heading', 'Overview')

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $first = explode(' ', trim(auth()->user()->name))[0];
    $short = function ($n) {
        $n = (float) $n;
        if ($n >= 10000000) return 'Rs. ' . number_format($n / 10000000, 2) . ' cr';
        if ($n >= 100000)   return 'Rs. ' . number_format($n / 100000, 2) . ' lac';
        return 'Rs. ' . number_format($n, 0);
    };
@endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ $first }}</h1>
    <p class="lede">
        {{ auth()->user()->primaryRole()?->name }} &middot; where the scheme stands today
    </p>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">Applications</div>
        <div class="tile-value">{{ number_format($h['all']) }}</div>
        <div class="tile-sub">{{ number_format($h['open']) }} open &middot; {{ $h['disposal_rate'] }}% disposed</div>
    </div>
    <div class="tile">
        <div class="tile-label">Regularized</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">{{ number_format((float) $h['area_sqft'] / 5445, 1) }} Kanal on record</div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">Rent secured</div>
        <div class="tile-value">{{ $short((float) $h['monthly_rent'] * 12) }}</div>
        <div class="tile-sub">a year</div>
    </div>
    <div class="tile {{ $h['recovery_rate'] < 50 ? 'is-warn' : '' }}">
        <div class="tile-label">Arrears recovered</div>
        <div class="tile-value">{{ $h['recovery_rate'] }}%</div>
        <div class="tile-sub">{{ $short($h['outstanding']) }} outstanding</div>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    <div class="card">
        <div class="card-head">
            <h3>Deadline performance</h3>
            @if ($p['assessment_overdue'] + $p['approval_overdue'] > 0)
                <span class="badge badge-danger">{{ $p['assessment_overdue'] + $p['approval_overdue'] }} overdue</span>
            @else
                <span class="badge badge-good">All on time</span>
            @endif
        </div>
        <div class="card-body">
            @foreach ([
                ['Assessment within 60 days', $p['assessment_ontime'], $p['assessment_overdue'], 'Clause 10(i)(e)'],
                ['Approval within one month', $p['approval_ontime'], $p['approval_overdue'], 'Clause 3(ii)(d)'],
            ] as [$label, $pct, $over, $clause])
                <div class="mb-4 last:mb-0">
                    <div class="flex items-baseline justify-between gap-2">
                        <strong class="text-[.88rem]">{{ $label }}</strong>
                        <span class="clause">{{ $clause }}</span>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="text-xl font-bold tabular-nums
                                    {{ $pct >= 90 ? 'text-pk-700' : ($pct >= 70 ? 'text-warn-700' : 'text-danger-600') }}">
                            {{ $pct }}%
                        </div>
                        <div class="sla-bar flex-1">
                            <div class="sla-fill {{ $pct >= 90 ? '' : ($pct >= 70 ? 'is-warn' : 'is-danger') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @if ($over > 0)
                        <div class="tile-sub text-danger-600">{{ number_format($over) }} past the limit</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="card-foot">
            <a href="{{ route('reports.glimpse') }}" class="btn btn-primary btn-sm">
                Performance at a glance @include('partials.icon', ['name' => 'arrow-right'])
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Needs attention</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>Awaiting deposit</dt>
                <dd><span class="badge badge-{{ $h['pending_pay'] > 0 ? 'warn' : 'good' }}">{{ number_format($h['pending_pay']) }}</span>
                    <span class="faint text-[.8rem]">not processed until paid</span></dd>
                <dt>Sub judice</dt>
                <dd><span class="badge badge-{{ $h['sub_judice'] > 0 ? 'warn' : 'good' }}">{{ number_format($h['sub_judice']) }}</span></dd>
                <dt>Objections open</dt>
                <dd><span class="badge badge-{{ $objections['open'] > 0 ? 'warn' : 'good' }}">{{ number_format($objections['open']) }}</span></dd>
                <dt>Fee collected</dt>
                <dd>{{ $short($h['fee_total']) }}
                    <span class="faint text-[.8rem]">({{ number_format($h['fee_count']) }} instruments)</span></dd>
            </dl>
            <hr class="divider">
            <div class="btn-row">
                <a href="{{ route('reports.executive') }}" class="btn btn-outline btn-sm">Consolidated report</a>
                <a href="{{ route('reports.registers') }}" class="btn btn-ghost btn-sm">Registers</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>Busiest districts</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>District</th><th class="num">Cases</th><th class="num">Regularized</th>
                <th class="num">Outstanding</th><th class="num">Recovery</th></tr>
            </thead>
            <tbody>
            @forelse ($byDistrict->take(8) as $d)
                @php $rec = (float) $d->assessed > 0 ? round((float) $d->recovered / (float) $d->assessed * 100) : 0; @endphp
                <tr>
                    <td>{{ $d->district?->name ?? 'Not recorded' }}</td>
                    <td class="num">{{ number_format($d->total) }}</td>
                    <td class="num">{{ number_format($d->regularized) }}</td>
                    <td class="num">{{ number_format((float) $d->outstanding, 0) }}</td>
                    <td class="num">
                        <span class="badge badge-{{ $rec >= 60 ? 'good' : ($rec >= 25 ? 'warn' : 'neutral') }}">{{ $rec }}%</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="row-muted">No applications yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>Arrears ageing</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead><tr><th>Age</th><th class="num">Cases</th><th class="num">Outstanding</th></tr></thead>
                <tbody>
                @foreach ($ageing as $bucket)
                    <tr>
                        <td>{{ $bucket['label'] }}</td>
                        <td class="num">{{ number_format($bucket['count']) }}</td>
                        <td class="num">{{ $short($bucket['amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-foot">
            <a href="{{ route('reports.executive') }}" class="btn btn-ghost btn-sm">Full ageing in consolidated report</a>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>Litigation &amp; stays</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>On register</dt><dd>{{ number_format($litigation['total']) }}</dd>
                <dt>Pending</dt><dd><span class="badge badge-{{ $litigation['pending'] > 0 ? 'warn' : 'good' }}">{{ number_format($litigation['pending']) }}</span></dd>
                <dt>Restraining orders</dt><dd><span class="badge badge-{{ $litigation['stays'] > 0 ? 'danger' : 'good' }}">{{ number_format($litigation['stays']) }}</span></dd>
            </dl>
            @if (($breaches['assessment']->count() + $breaches['approval']->count()) > 0)
                <hr class="divider">
                <p class="mb-2 text-[.85rem]">
                    <strong class="text-danger-600">{{ $breaches['assessment']->count() + $breaches['approval']->count() }}</strong>
                    statutory deadline{{ ($breaches['assessment']->count() + $breaches['approval']->count()) === 1 ? '' : 's' }} breached —
                    named in the consolidated report.
                </p>
            @endif
            <div class="btn-row">
                <a href="{{ route('reports.glimpse') }}" class="btn btn-primary btn-sm">At a glance (PDF / Word / Excel)</a>
                <a href="{{ route('reports.registers', ['register' => 'litigation']) }}" class="btn btn-outline btn-sm">Litigation register</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>Intake vs disposal — last 12 months</h3></div>
    <div class="card-body">
        @php
            $max = max(array_merge($monthly->values()->all(), $disposal->values()->all(), [1]));
        @endphp
        <div class="flex items-end gap-1 sm:gap-2 h-[160px]">
            @foreach ($monthly as $ym => $n)
                @php $d = (int) ($disposal[$ym] ?? 0); @endphp
                <div class="flex-1 flex flex-col items-center gap-1 h-full min-w-0" title="Intake {{ $n }} · Regularized {{ $d }}">
                    <div class="text-[.65rem] tabular-nums muted leading-none">{{ $n }}/{{ $d }}</div>
                    <div class="w-full flex gap-0.5 items-end mt-auto" style="height:{{ max(8, round(max($n, $d) / $max * 100)) }}%">
                        <div class="flex-1 bg-pk-600 rounded-t" style="height:{{ $n > 0 ? max(12, round($n / max($n, $d, 1) * 100)) : 8 }}%"></div>
                        <div class="flex-1 bg-gold-500 rounded-t opacity-90" style="height:{{ $d > 0 ? max(12, round($d / max($n, $d, 1) * 100)) : 8 }}%; background: var(--color-warn-600, #b45309)"></div>
                    </div>
                    <div class="faint text-[.62rem] whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->format('M') }}
                    </div>
                </div>
            @endforeach
        </div>
        <p class="faint text-[.75rem] mt-3 mb-0">
            Dark bars = applications submitted &middot; amber bars = cases regularized
        </p>
    </div>
</div>

@endsection
