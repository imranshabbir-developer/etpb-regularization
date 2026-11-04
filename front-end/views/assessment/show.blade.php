@extends('layouts.app')

@section('title', __('Rent assessment'))
@section('heading', __('Rent assessment — :no', ['no' => $application->application_no]))

@section('content')

@php
    $decided = $round && $round->status === 'DECIDED';
    $rateUnits = [
        'PER_SQFT_PER_MONTH'  => __('per sqft / month'),
        'PER_MARLA_PER_MONTH' => __('per Marla / month'),
        'PER_MONTH_TOTAL'     => __('per month (total)'),
        'PER_SQFT_VALUE'      => __('per sqft (capital value)'),
        'PER_MARLA_VALUE'     => __('per Marla (capital value)'),
        'TOTAL_VALUE'         => __('total capital value'),
    ];
@endphp

<div class="page-head">
    <h1>{{ __('Rent assessment') }}</h1>
    <p class="lede">
        {{ $application->applicant?->nameWithParentage(true) }} &mdash;
        {{ $application->property?->identity(true) }},
        {{ __($application->property?->district?->name ?? '') }}
        @if ($areaSqft)
            &middot; {{ __(':area sqft', ['area' => number_format((float) $areaSqft, 2)]) }}
        @endif
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; {{ __('Case file') }}</a>
        <span class="badge badge-{{ $application->statusTone() }}">{{ __($application->statusLabel()) }}</span>
        <span class="clause">{{ __('Clause :no', ['no' => '10']) }}</span>
        <span class="clause">{{ __('Clause 11(ii) — 8% p.a.') }}</span>
    </div>
</div>

@if (! $round)
    {{-- ---------- No round yet ---------- --}}
    <div class="card container-narrow">
        <div class="card-head"><h3>{{ __('Open an assessment round') }}</h3></div>
        <div class="card-body">
            <div class="alert alert-info">
                @include('partials.icon', ['name' => 'info'])
                <div>
                    <p class="mb-0">
                        {!! __('Clause 10(i) requires assessment with effect from <strong>01-07-2006</strong>. The rent you determine is the rent as at the anchor date below: later years are enhanced at 8% per annum and earlier years are back-cast at the same rate, because arrears reach back to 2000 while the assessment is anchored in 2006.') !!}
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('assessment.rounds.store', $application) }}">
                @csrf
                <div class="grid-2">
                    <div class="field">
                        <label for="round_type">{{ __('Round type') }}</label>
                        <select id="round_type" name="round_type" class="select">
                            <option value="INITIAL">{{ __('Initial assessment') }}</option>
                            <option value="PERIODICAL">{{ __('Periodical re-assessment (Clause 11(i))') }}</option>
                            <option value="REVISION">{{ __('Revision') }}</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="base_date">{{ __('Base date') }}</label>
                        <input type="date" id="base_date" name="base_date" class="input"
                               value="{{ old('base_date', $defaults['base_date']) }}" required>
                        <p class="hint">{{ __('Clause 10(i) fixes this at 01-07-2006.') }}</p>
                    </div>
                    <div class="field">
                        <label for="effective_from">{{ __('Anchor date for the determined rent') }}</label>
                        <input type="date" id="effective_from" name="effective_from" class="input"
                               value="{{ old('effective_from', $defaults['base_date']) }}" required>
                        <p class="hint">{{ __('The rent you fix is the rent as at this date.') }}</p>
                    </div>
                    <div class="field">
                        <label for="enhancement_rate">{{ __('Enhancement rate (% per annum)') }}</label>
                        <input type="text" id="enhancement_rate" name="enhancement_rate" class="input"
                               value="{{ old('enhancement_rate', $defaults['rate']) }}" required inputmode="decimal">
                        <p class="hint">{{ __('Clause 11(ii) prescribes 8%.') }}</p>
                    </div>
                </div>

                <div class="field">
                    <label for="enhancement_method">{{ __('Enhancement method') }}</label>
                    <select id="enhancement_method" name="enhancement_method" class="select">
                        <option value="COMPOUND" @selected($defaults['method'] === 'COMPOUND')>{{ __('Compound — base × (1.08)ⁿ') }}</option>
                        <option value="SIMPLE" @selected($defaults['method'] === 'SIMPLE')>{{ __('Simple — base × (1 + 0.08n)') }}</option>
                    </select>
                    <p class="hint">
                        {{ __('Clause 11(ii) does not say which applies. Over 24 years compound gives about 6.34× the base and simple about 2.92×. The method is recorded on every schedule generated from this round.') }}
                    </p>
                </div>

                <button class="btn btn-primary" type="submit">{{ __('Open round') }}</button>
            </form>
        </div>
    </div>
@else

    {{-- ---------- Round header ---------- --}}
    <div class="tiles">
        <div class="tile">
            <div class="tile-label">{{ __('Round') }}</div>
            <div class="tile-value">{{ $round->round_no }}</div>
            <div class="tile-sub">{{ __(ucfirst(strtolower(str_replace('_', ' ', $round->round_type)))) }} &middot; {{ __($round->status ?? '') }}</div>
        </div>
        <div class="tile">
            <div class="tile-label">{{ __('Anchor') }}</div>
            <div class="tile-value" style="font-size:1.15rem">
                {{ \Illuminate\Support\Carbon::parse($round->effective_from)->translatedFormat('d-m-Y') }}
            </div>
            <div class="tile-sub">{{ __('Base :date', ['date' => \Illuminate\Support\Carbon::parse($round->base_date)->translatedFormat('d-m-Y')]) }}</div>
        </div>
        <div class="tile is-gold">
            <div class="tile-label">{{ __('Enhancement') }}</div>
            <div class="tile-value" style="font-size:1.15rem">{{ __(':rate% p.a.', ['rate' => rtrim(rtrim($round->enhancement_rate, '0'), '.')]) }}</div>
            <div class="tile-sub">{{ __(ucfirst(strtolower($round->enhancement_method))) }} &middot; {{ __(':years-year cycle', ['years' => $round->reassessment_cycle_years]) }}</div>
        </div>
        @if ($round->determined_monthly_rent)
            <div class="tile">
                <div class="tile-label">{{ __('Rent determined') }}</div>
                <div class="tile-value">Rs. {{ number_format((float) $round->determined_monthly_rent, 0) }}</div>
                <div class="tile-sub">{{ __('per month, at the anchor year') }}</div>
            </div>
        @elseif ($round->proposed_monthly_rent)
            <div class="tile is-warn">
                <div class="tile-label">{{ __('Rent proposed') }}</div>
                <div class="tile-value">Rs. {{ number_format((float) $round->proposed_monthly_rent, 0) }}</div>
                <div class="tile-sub">{{ __('not yet fixed') }}</div>
            </div>
        @endif
    </div>

    <div class="grid-2" style="gap:1.15rem;align-items:start">
        <div>
            {{-- ---------- Rate inputs ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>{{ __('Evidence of value') }}</h3>
                    <span class="badge badge-neutral">{{ $round->rateInputs->count() }}</span>
                </div>

                @if ($round->rateInputs->isEmpty())
                    <div class="empty">
                        @include('partials.icon', ['name' => 'scale'])
                        <p class="mb-0">{{ __('No rate inputs recorded.') }}</p>
                        <p class="hint">{{ __('The determination rests on these; record what was relied on.') }}</p>
                    </div>
                @else
                    <div class="table-wrap" style="border:0;border-radius:0">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>{{ __('Source') }}</th><th class="num">{{ __('Rate') }}</th><th>{{ __('Basis') }}</th><th>{{ __('Reference') }}</th><th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($round->rateInputs as $in)
                                <tr>
                                    <td>
                                        {{ __($in->rateSource?->name ?? '') }}
                                        @if ($in->rateSource?->is_operative)
                                            <span class="badge badge-gold">{{ __('Operative') }}</span>
                                        @endif
                                    </td>
                                    <td class="num">Rs. {{ number_format((float) $in->rate_value, 2) }}</td>
                                    <td class="nowrap faint">{{ $rateUnits[$in->rate_unit] ?? $in->rate_unit }}</td>
                                    <td class="faint" style="font-size:.8rem">
                                        {{ $in->notification_no ?: $in->report_no ?: '—' }}
                                        @if ($in->notification_date)
                                            <div>{{ \Illuminate\Support\Carbon::parse($in->notification_date)->translatedFormat('d-m-Y') }}</div>
                                        @endif
                                        @if ($in->valuator_name)<div>{{ $in->valuator_name }}</div>@endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $decided)
                                            <form method="POST" action="{{ route('assessment.rates.destroy', $in) }}">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-ghost btn-sm" type="submit">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('do', 'assessment.rate_inputs')
                    @unless ($decided)
                        <div class="card-foot">
                            <form method="POST" action="{{ route('assessment.rates.store', $round) }}">
                                @csrf
                                <div class="grid-3">
                                    <div class="field">
                                        <label for="rate_source_id">{{ __('Source') }}</label>
                                        <select id="rate_source_id" name="rate_source_id" class="select" required>
                                            @foreach ($rateSources as $s)
                                                <option value="{{ $s->id }}">{{ __($s->name ?? '') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="rate_value">{{ __('Rate (Rs.)') }}</label>
                                        <input type="text" id="rate_value" name="rate_value" class="input"
                                               inputmode="decimal" required>
                                    </div>
                                    <div class="field">
                                        <label for="rate_unit">{{ __('Basis') }}</label>
                                        <select id="rate_unit" name="rate_unit" class="select">
                                            @foreach ($rateUnits as $v => $l)
                                                <option value="{{ $v }}">{{ $l }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="notification_no">{{ __('Notification / report no.') }}</label>
                                        <input type="text" id="notification_no" name="notification_no" class="input">
                                    </div>
                                    <div class="field">
                                        <label for="notification_date">{{ __('Dated') }}</label>
                                        <input type="date" id="notification_date" name="notification_date" class="input">
                                    </div>
                                    <div class="field">
                                        <label for="valuator_name">{{ __('Valuator (if any)') }}</label>
                                        <input type="text" id="valuator_name" name="valuator_name" class="input">
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" type="submit">
                                    @include('partials.icon', ['name' => 'plus']) {{ __('Add rate input') }}
                                </button>
                            </form>
                        </div>
                    @endunless
                @endcan
            </div>

            {{-- ---------- Comparables ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>{{ __('Adjoining properties in similar circumstances') }}</h3>
                    <div class="card-actions"><span class="clause">{{ __('Clause :no', ['no' => '2(i)(l)']) }}</span></div>
                </div>

                @if ($round->comparables->isEmpty())
                    <div class="empty">
                        @include('partials.icon', ['name' => 'map'])
                        <p class="mb-0">{{ __('No comparables recorded.') }}</p>
                    </div>
                @else
                    <div class="table-wrap" style="border:0;border-radius:0">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>{{ __('Property') }}</th><th class="num">{{ __('Area') }}</th><th class="num">{{ __('Monthly rent') }}</th>
                                <th class="num">{{ __('Rs./sqft') }}</th><th>{{ __('Source') }}</th><th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($round->comparables as $c)
                                <tr>
                                    <td>
                                        {{ $c->property_description }}
                                        @if ($c->address)<div class="faint" style="font-size:.78rem">{{ $c->address }}</div>@endif
                                    </td>
                                    <td class="num">{{ $c->area_sqft ? number_format((float) $c->area_sqft, 0) : '—' }}</td>
                                    <td class="num">Rs. {{ number_format((float) $c->monthly_rent, 2) }}</td>
                                    <td class="num">
                                        @if ($c->area_sqft && (float) $c->area_sqft > 0)
                                            {{ number_format((float) $c->monthly_rent / (float) $c->area_sqft, 2) }}
                                        @else — @endif
                                    </td>
                                    <td class="faint" style="font-size:.8rem">{{ $c->information_source ?: '—' }}</td>
                                    <td class="text-end">
                                        @if (! $decided)
                                            <form method="POST" action="{{ route('assessment.comparables.destroy', $c) }}">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-ghost btn-sm" type="submit">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('do', 'assessment.rate_inputs')
                    @unless ($decided)
                        <div class="card-foot">
                            <form method="POST" action="{{ route('assessment.comparables.store', $round) }}">
                                @csrf
                                <div class="grid-3">
                                    <div class="field">
                                        <label for="property_description">{{ __('Property') }}</label>
                                        <input type="text" id="property_description" name="property_description"
                                               class="input" required>
                                    </div>
                                    <div class="field">
                                        <label for="c_area">{{ __('Area (sqft)') }}</label>
                                        <input type="text" id="c_area" name="area_sqft" class="input" inputmode="decimal">
                                    </div>
                                    <div class="field">
                                        <label for="c_rent">{{ __('Monthly rent (Rs.)') }}</label>
                                        <input type="text" id="c_rent" name="monthly_rent" class="input"
                                               inputmode="decimal" required>
                                    </div>
                                    <div class="field">
                                        <label for="c_usage">{{ __('Usage') }}</label>
                                        <select id="c_usage" name="usage_type" class="select">
                                            <option value="RESIDENTIAL">{{ __('Residential') }}</option>
                                            <option value="COMMERCIAL">{{ __('Commercial') }}</option>
                                            <option value="RESIDENTIAL_CUM_COMMERCIAL">{{ __('Residential-cum-commercial') }}</option>
                                            <option value="OTHER">{{ __('Other') }}</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="c_distance">{{ __('Distance (m)') }}</label>
                                        <input type="text" id="c_distance" name="distance_meters" class="input" inputmode="decimal">
                                    </div>
                                    <div class="field">
                                        <label for="c_source">{{ __('Source of information') }}</label>
                                        <input type="text" id="c_source" name="information_source" class="input">
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" type="submit">
                                    @include('partials.icon', ['name' => 'plus']) {{ __('Add comparable') }}
                                </button>
                            </form>
                        </div>
                    @endunless
                @endcan
            </div>

            {{-- ---------- The schedule ---------- --}}
            @if ($schedule->isNotEmpty())
                <div class="card">
                    <div class="card-head">
                        <h3>{{ __('Year-by-year schedule') }}</h3>
                        <span class="badge badge-neutral">{{ __(':count years', ['count' => $schedule->count()]) }}</span>
                        <div class="card-actions">
                            <a href="{{ route('arrears.index', $application) }}" class="btn btn-outline btn-sm">
                                {{ __('Arrears ledger') }} &rarr;
                            </a>
                        </div>
                    </div>
                    <div class="table-wrap" style="border:0;border-radius:0;max-height:460px;overflow-y:auto">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>{{ __('Rent year') }}</th><th>{{ __('Period') }}</th>
                                <th class="num">{{ __('Monthly') }}</th><th class="num">{{ __('Months') }}</th><th class="num">{{ __('Annual') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($schedule as $s)
                                <tr class="{{ $s->is_milestone_year ? 'is-milestone' : '' }}">
                                    <td>
                                        <strong>{{ $s->year }}</strong>
                                        @if ($s->is_reassessment_year)
                                            <span class="badge badge-info" title="{{ __('Clause :no', ['no' => '11(i)']) }}">{{ __('Re-assessment') }}</span>
                                        @endif
                                    </td>
                                    <td class="nowrap faint" style="font-size:.8rem">
                                        {{ \Illuminate\Support\Carbon::parse($s->period_from)->translatedFormat('d-m-Y') }}
                                        &ndash;
                                        {{ \Illuminate\Support\Carbon::parse($s->period_to)->translatedFormat('d-m-Y') }}
                                    </td>
                                    <td class="num">{{ number_format((float) $s->monthly_rent, 2) }}</td>
                                    <td class="num faint">
                                        {{ rtrim(rtrim(number_format((float) $s->annual_rent / max(0.0001, (float) $s->monthly_rent), 2), '0'), '.') }}
                                    </td>
                                    <td class="num"><strong>{{ number_format((float) $s->annual_rent, 2) }}</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-foot">
                        <p class="mb-0 faint" style="font-size:.8rem">
                            {{ __('A rent year runs 1 July to 30 June, because both statutory anchors — 01-07-2000 for arrears and 01-07-2006 for assessment — fall on 1 July. Highlighted rows are the milestone years reported to higher authorities.') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <div>
            {{-- ---------- Propose ---------- --}}
            @can('do', 'assessment.propose')
                @unless ($decided)
                    <div class="card">
                        <div class="card-head">
                            <h3>{{ __('Proposed assessment') }}</h3>
                            <div class="card-actions"><span class="clause">{{ __('Clause :no', ['no' => '10(i)(a)–(b)']) }}</span></div>
                        </div>
                        <div class="card-body">
                            <p class="muted" style="font-size:.86rem">
                                {{ __('The proposal is what goes on public display and into the notice. It is not the fixation.') }}
                            </p>
                            <form method="POST" action="{{ route('assessment.propose', $round) }}">
                                @csrf
                                <div class="field">
                                    <label for="proposed_monthly_rent">{{ __('Proposed monthly rent (Rs.)') }}</label>
                                    <input type="text" id="proposed_monthly_rent" name="proposed_monthly_rent"
                                           class="input" inputmode="decimal" required
                                           value="{{ $round->proposed_monthly_rent }}">
                                </div>
                                <button class="btn btn-primary" type="submit" style="width:100%">
                                    {{ __('Record proposal') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endunless
            @endcan

            {{-- ---------- Determine ---------- --}}
            @can('do', 'assessment.fix_rent')
                <div class="card">
                    <div class="card-head">
                        <h3>{{ $decided ? __('Determination') : __('Fix the rent') }}</h3>
                        <div class="card-actions"><span class="clause">{{ __('Clause :no', ['no' => '10(i)(d)']) }}</span></div>
                    </div>
                    <div class="card-body">
                        @if ($decided && $decision)
                            <dl class="kv">
                                <dt>{{ __('Rent fixed') }}</dt>
                                <dd><strong>Rs. {{ number_format((float) $decision->determined_monthly_rent, 2) }}</strong> {{ __('per month') }}</dd>
                                <dt>{{ __('Decided') }}</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($decision->decided_at)->translatedFormat('d-m-Y H:i') }}</dd>
                            </dl>
                            <h4 class="mt-2">{{ __('Reasons recorded') }}</h4>
                            <p class="muted" style="white-space:pre-wrap;font-size:.87rem">{{ $decision->reasons }}</p>
                            @if ($decision->objections_considered)
                                <h4>{{ __('Objections considered') }}</h4>
                                <p class="muted" style="white-space:pre-wrap;font-size:.87rem">{{ $decision->objections_considered }}</p>
                            @endif
                        @else
                            <div class="alert alert-warn">
                                @include('partials.icon', ['name' => 'alert'])
                                <div>
                                    <p class="mb-0">
                                        {{ __('Fixing the rent generates the schedule back to :from and builds the arrears ledger. Reasons are mandatory and become part of the record.', ['from' => $application->possession ? \Illuminate\Support\Carbon::parse($application->possession->arrears_from)->translatedFormat('Y') : __('the arrears start')]) }}
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('assessment.determine', $round) }}" id="determineForm">
                                @csrf
                                <div class="field">
                                    <label for="determined_monthly_rent">{{ __('Monthly rent determined (Rs.)') }} <span class="req">*</span></label>
                                    <input type="text" id="determined_monthly_rent" name="determined_monthly_rent"
                                           class="input" inputmode="decimal" required
                                           value="{{ old('determined_monthly_rent', $round->proposed_monthly_rent) }}">
                                </div>

                                @if ($areaSqft)
                                    <div class="field">
                                        <label for="rate_per_sqft">{{ __('Equivalent rate per sqft') }}</label>
                                        <input type="text" id="rate_per_sqft" name="rate_per_sqft" class="input"
                                               inputmode="decimal" readonly>
                                        <p class="hint">{{ __('Derived from :area sqft.', ['area' => number_format((float) $areaSqft, 2)]) }}</p>
                                    </div>
                                @endif

                                <div id="rentPreview" class="alert alert-info" hidden>
                                    @include('partials.icon', ['name' => 'info'])
                                    <div style="min-width:0">
                                        <strong id="previewTotal"></strong>
                                        <div id="previewMeta" class="faint" style="font-size:.79rem"></div>
                                        <div id="previewYears" class="faint" style="font-size:.79rem;margin-top:.3rem"></div>
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="reasons">{{ __('Reasons') }} <span class="req">*</span></label>
                                    <textarea id="reasons" name="reasons" class="textarea" style="min-height:150px"
                                              required minlength="40">{{ old('reasons') }}</textarea>
                                    <p class="hint">
                                        {{ __('Set out what was considered — the rates relied on, the comparables, the objections — and why this figure follows.') }}
                                    </p>
                                </div>

                                <div class="field">
                                    <label for="objections_considered">{{ __('Objections considered') }}</label>
                                    <textarea id="objections_considered" name="objections_considered"
                                              class="textarea" style="min-height:80px">{{ old('objections_considered') }}</textarea>
                                </div>

                                <button class="btn btn-primary btn-lg" type="submit" style="width:100%">
                                    @include('partials.icon', ['name' => 'check']) {{ __('Fix rent and build ledger') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endcan

            {{-- ---------- Milestone grid ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>{{ __('Milestone years') }}</h3>
                    <div class="card-actions"><span class="clause">{{ __('Report grid') }}</span></div>
                </div>
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead><tr><th>{{ __('Year') }}</th><th class="num">{{ __('Monthly') }}</th><th class="num">{{ __('Annual') }}</th></tr></thead>
                        <tbody>
                        @foreach ($milestones as $m)
                            <tr class="{{ $m['in_scope'] ? '' : 'row-muted' }}">
                                <td><strong>{{ $m['year'] }}</strong></td>
                                <td class="num">{{ $m['monthly_rent'] ? number_format((float) $m['monthly_rent'], 2) : '—' }}</td>
                                <td class="num">{{ $m['annual_rent'] ? number_format((float) $m['annual_rent'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>{{ __('Next steps') }}</h3></div>
                <div class="card-body">
                    <div class="btn-row">
                        <a href="{{ route('due-process.index', $application) }}" class="btn btn-outline">
                            @include('partials.icon', ['name' => 'inbox']) {{ __('Notices & objections') }}
                        </a>
                        <a href="{{ route('arrears.index', $application) }}" class="btn btn-outline">
                            @include('partials.icon', ['name' => 'cash']) {{ __('Arrears') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if ($round && ! $decided)
<script>
(function () {
    var rentInput = document.getElementById('determined_monthly_rent');
    if (!rentInput) return;

    var perSqft  = document.getElementById('rate_per_sqft');
    var area     = @json($areaSqft ? (float) $areaSqft : null);
    var box      = document.getElementById('rentPreview');
    var total    = document.getElementById('previewTotal');
    var meta     = document.getElementById('previewMeta');
    var years    = document.getElementById('previewYears');
    var token    = document.querySelector('meta[name="csrf-token"]').content;
    var url      = @json(route('assessment.preview', $round));
    var totalText = @json(__('Approximately Rs. :total in total rent across :years year(s)'));
    var metaText  = {{ \Illuminate\Support\Js::from(__(':rate% per annum, :method, anchored at rent year :year')) }};
    var methods   = { COMPOUND: @json(__('compound')), SIMPLE: @json(__('simple')) };
    var timer;

    function run() {
        var v = rentInput.value.replace(/,/g, '');
        if (perSqft && area && v && !isNaN(v)) {
            perSqft.value = (parseFloat(v) / area).toFixed(4);
        }
        clearTimeout(timer);
        if (!v || isNaN(v) || parseFloat(v) <= 0) { box.hidden = true; return; }

        timer = setTimeout(function () {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ monthly_rent: v })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { box.hidden = true; return; }
                total.textContent = totalText
                    .replace(':total', function () { return d.total; })
                    .replace(':years', function () { return d.years; });
                meta.textContent  = metaText
                    .replace(':rate', function () { return d.rate; })
                    .replace(':method', function () { return methods[d.method] || String(d.method).toLowerCase(); })
                    .replace(':year', function () { return d.anchor_year; });
                years.textContent = d.milestones.map(function (m) {
                    return m.year + ': ' + m.monthly;
                }).join('   ·   ');
                box.hidden = false;
            })
            .catch(function () { box.hidden = true; });
        }, 350);
    }

    rentInput.addEventListener('input', run);
    run();
})();
</script>
@endif
@endpush
