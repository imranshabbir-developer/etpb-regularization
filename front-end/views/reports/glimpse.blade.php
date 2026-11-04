@extends('layouts.app')

@section('title', __('Performance at a glance'))
@section('heading', __('Performance at a glance'))

@section('content')

@php
    $h = $headline;
    $p = $performance;
    $rs = fn ($n) => 'Rs. ' . number_format((float) $n, 0);
    $short = function ($n) {
        $n = (float) $n;
        if ($n >= 10000000) return 'Rs. ' . number_format($n / 10000000, 2) . ' ' . __('cr');
        if ($n >= 100000)   return 'Rs. ' . number_format($n / 100000, 2) . ' ' . __('lac');
        return 'Rs. ' . number_format($n, 0);
    };
@endphp

<div class="page-head">
    <h1>{{ __('Performance at a glance') }}</h1>
    <p class="lede">
        {{ $districtName ? __('District :name', ['name' => __($districtName)]) : __('All districts') }} &middot;
        {{ __('as at :date', ['date' => $generatedAt->translatedFormat('d F Y')]) }}
    </p>
</div>

<div class="card no-print">
    <div class="card-body tight">
        <form method="GET" action="{{ route('reports.glimpse') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="field mb-0 min-w-[200px]">
                <label for="district">{{ __('District') }}</label>
                <select id="district" name="district" class="select" onchange="this.form.submit()">
                    <option value="">{{ __('All districts') }}</option>
                    @foreach ($districts as $d)
                        <option value="{{ $d->id }}" @selected($districtId === $d->id)>{{ __($d->name) }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="btn btn-primary btn-sm" type="submit">{{ __('Apply') }}</button></noscript>
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
        <div class="tile-label">{{ __('Applications') }}</div>
        <div class="tile-value">{{ number_format($h['all']) }}</div>
        <div class="tile-sub">{{ __(':open open · :rate% disposed', ['open' => number_format($h['open']), 'rate' => $h['disposal_rate']]) }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Regularized') }}</div>
        <div class="tile-value">{{ number_format($h['regularized']) }}</div>
        <div class="tile-sub">
            {{ __(':kanal Kanal brought on record', ['kanal' => number_format((float) $h['area_sqft'] / 5445, 1)]) }}
        </div>
    </div>
    <div class="tile is-gold">
        <div class="tile-label">{{ __('Rent secured') }}</div>
        <div class="tile-value">{{ $short((float) $h['monthly_rent'] * 12) }}</div>
        <div class="tile-sub">{{ __('a year · :amount a month', ['amount' => $rs($h['monthly_rent'])]) }}</div>
    </div>
    <div class="tile {{ $h['recovery_rate'] < 50 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Arrears recovered') }}</div>
        <div class="tile-value">{{ $h['recovery_rate'] }}%</div>
        <div class="tile-sub">{{ __(':amount still outstanding', ['amount' => $short($h['outstanding'])]) }}</div>
    </div>
</div>

{{-- ---------- Deadline performance ---------- --}}
<div class="card">
    <div class="card-head">
        <h3>{{ __('Are the statutory deadlines being met?') }}</h3>
        @if ($p['assessment_overdue'] + $p['approval_overdue'] > 0)
            <span class="badge badge-danger">{{ __(':count overdue', ['count' => $p['assessment_overdue'] + $p['approval_overdue']]) }}</span>
        @else
            <span class="badge badge-good">{{ __('All on time') }}</span>
        @endif
    </div>
    <div class="card-body">
        <div class="grid-2 gap-y-4">
            @foreach ([
                [__('Assessment within 60 days'), $p['assessment_ontime'], $p['assessment_live'], $p['assessment_overdue'], __('Clause 10(i)(e)')],
                [__('Approval within one month'), $p['approval_ontime'], $p['approval_live'], $p['approval_overdue'], __('Clause 3(ii)(d)')],
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
                        {{ __(':count in hand', ['count' => number_format($live)]) }}
                        @if ($over > 0) &middot; <strong class="text-danger-600">{{ __(':count overdue', ['count' => number_format($over)]) }}</strong> @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($p['avg_days'] !== null)
            <hr class="divider">
            <p class="mb-0 muted text-[.88rem]">
                {{ __('A case takes on average') }} <strong>{{ __(':count days', ['count' => number_format($p['avg_days'])]) }}</strong>
                {{ __('from submission to regularization.') }}
                {{ __('Of :total approvals decided so far,', ['total' => number_format($p['approvals_total'])]) }}
                <strong>{{ $p['approvals_pct'] }}%</strong> {{ __('were within the month.') }}
            </p>
        @endif
    </div>
</div>

<div class="grid-2 items-start gap-[1.15rem]">
    {{-- ---------- Where the work is ---------- --}}
    <div class="card">
        <div class="card-head"><h3>{{ __('Busiest districts') }}</h3></div>
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>{{ __('District') }}</th><th class="num">{{ __('Cases') }}</th><th class="num">{{ __('Done') }}</th><th class="num">{{ __('Recovery') }}</th></tr>
                </thead>
                <tbody>
                @forelse ($byDistrict->take(8) as $d)
                    @php $rec = (float) $d->assessed > 0 ? round((float) $d->recovered / (float) $d->assessed * 100) : 0; @endphp
                    <tr>
                        <td>{{ __($d->district?->name ?? 'Not recorded') }}</td>
                        <td class="num">{{ number_format($d->total) }}</td>
                        <td class="num">{{ number_format($d->regularized) }}</td>
                        <td class="num">
                            <span class="badge badge-{{ $rec >= 60 ? 'good' : ($rec >= 25 ? 'warn' : 'neutral') }}">
                                {{ $rec }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="row-muted">{{ __('No applications yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---------- Attention ---------- --}}
    <div class="card">
        <div class="card-head"><h3>{{ __('Needs attention') }}</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>{{ __('Awaiting the Rs. 5,000 deposit') }}</dt>
                <dd>
                    <span class="badge badge-{{ $h['pending_pay'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($h['pending_pay']) }}
                    </span>
                    <span class="faint text-[.8rem]">{{ __('not processed until paid') }}</span>
                </dd>

                <dt>{{ __('Sub judice') }}</dt>
                <dd>
                    <span class="badge badge-{{ $h['sub_judice'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($h['sub_judice']) }}
                    </span>
                    <span class="faint text-[.8rem]">{{ __('before a court, or stayed') }}</span>
                </dd>

                <dt>{{ __('Objections still open') }}</dt>
                <dd>
                    <span class="badge badge-{{ $objections['open'] > 0 ? 'warn' : 'good' }}">
                        {{ number_format($objections['open']) }}
                    </span>
                    <span class="faint text-[.8rem]">{{ __('rent cannot be fixed until decided') }}</span>
                </dd>

                <dt>{{ __('Fee collected') }}</dt>
                <dd>{{ $rs($h['fee_total']) }}
                    <span class="faint text-[.8rem]">({{ __(':count instruments', ['count' => number_format($h['fee_count'])]) }})</span></dd>
            </dl>

            @can('do', 'reports.executive')
                <hr class="divider">
                <a href="{{ route('reports.executive', array_filter(['district' => $districtId])) }}"
                   class="btn btn-primary btn-sm">
                    {{ __('Full consolidated report') }} &rarr;
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- ---------- Intake vs disposal ---------- --}}
@php
    $trend = [
        'labels' => $monthly->keys()->values(),
        'intake' => $monthly->values()->values(),
        'disposal' => ($disposal ?? collect())->values()->values(),
        'intakeLabel' => __('Submitted'),
        'disposalLabel' => __('Regularized'),
    ];
    $ageChart = [
        'labels' => collect($ageing ?? [])->pluck('label')->map(fn ($l) => __($l))->values(),
        'values' => collect($ageing ?? [])->map(fn ($b) => (float) $b['amount'])->values(),
        'label' => __('Outstanding'),
        'money' => true,
    ];
@endphp

<div class="grid-2 items-start gap-[1.15rem]">
    <div class="card">
        <div class="card-head"><h3>{{ __('Intake vs regularized (12 months)') }}</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame">
                <canvas data-etpb-chart="line-dual" data-etpb-source="glimpse-trend-data"
                        aria-label="{{ __('Monthly intake and regularization') }}"></canvas>
            </div>
            <script type="application/json" id="glimpse-trend-data">@json($trend)</script>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h3>{{ __('Arrears ageing') }}</h3></div>
        <div class="card-body chart-card-body">
            <div class="chart-frame is-md">
                <canvas data-etpb-chart="bar" data-etpb-source="glimpse-age-data"
                        aria-label="{{ __('Outstanding arrears by age') }}"></canvas>
            </div>
            <script type="application/json" id="glimpse-age-data">@json($ageChart)</script>
        </div>
    </div>
</div>

@endsection
