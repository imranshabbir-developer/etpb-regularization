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

@endsection
