@extends('layouts.app')

@section('title', 'Rent assessment')
@section('heading', 'Rent assessment — ' . $application->application_no)

@section('content')

@php
    $decided = $round && $round->status === 'DECIDED';
    $rateUnits = [
        'PER_SQFT_PER_MONTH'  => 'per sqft / month',
        'PER_MARLA_PER_MONTH' => 'per Marla / month',
        'PER_MONTH_TOTAL'     => 'per month (total)',
        'PER_SQFT_VALUE'      => 'per sqft (capital value)',
        'PER_MARLA_VALUE'     => 'per Marla (capital value)',
        'TOTAL_VALUE'         => 'total capital value',
    ];
@endphp

<div class="page-head">
    <h1>Rent assessment</h1>
    <p class="lede">
        {{ $application->applicant?->nameWithParentage() }} &mdash;
        {{ $application->property?->identity() }},
        {{ $application->property?->district?->name }}
        @if ($areaSqft)
            &middot; {{ number_format((float) $areaSqft, 2) }} sqft
        @endif
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; Case file</a>
        <span class="badge badge-{{ $application->statusTone() }}">{{ $application->statusLabel() }}</span>
        <span class="clause">Clause 10</span>
        <span class="clause">Clause 11(ii) — 8% p.a.</span>
    </div>
</div>

@if (! $round)
    {{-- ---------- No round yet ---------- --}}
    <div class="card container-narrow">
        <div class="card-head"><h3>Open an assessment round</h3></div>
        <div class="card-body">
            <div class="alert alert-info">
                @include('partials.icon', ['name' => 'info'])
                <div>
                    <p class="mb-0">
                        Clause 10(i) requires assessment with effect from <strong>01-07-2006</strong>.
                        The rent you determine is the rent as at the anchor date below: later years
                        are enhanced at 8% per annum and earlier years are back-cast at the same
                        rate, because arrears reach back to 2000 while the assessment is anchored
                        in 2006.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('assessment.rounds.store', $application) }}">
                @csrf
                <div class="grid-2">
                    <div class="field">
                        <label for="round_type">Round type</label>
                        <select id="round_type" name="round_type" class="select">
                            <option value="INITIAL">Initial assessment</option>
                            <option value="PERIODICAL">Periodical re-assessment (Clause 11(i))</option>
                            <option value="REVISION">Revision</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="base_date">Base date</label>
                        <input type="date" id="base_date" name="base_date" class="input"
                               value="{{ old('base_date', $defaults['base_date']) }}" required>
                        <p class="hint">Clause 10(i) fixes this at 01-07-2006.</p>
                    </div>
                    <div class="field">
                        <label for="effective_from">Anchor date for the determined rent</label>
                        <input type="date" id="effective_from" name="effective_from" class="input"
                               value="{{ old('effective_from', $defaults['base_date']) }}" required>
                        <p class="hint">The rent you fix is the rent as at this date.</p>
                    </div>
                    <div class="field">
                        <label for="enhancement_rate">Enhancement rate (% per annum)</label>
                        <input type="text" id="enhancement_rate" name="enhancement_rate" class="input"
                               value="{{ old('enhancement_rate', $defaults['rate']) }}" required inputmode="decimal">
                        <p class="hint">Clause 11(ii) prescribes 8%.</p>
                    </div>
                </div>

                <div class="field">
                    <label for="enhancement_method">Enhancement method</label>
                    <select id="enhancement_method" name="enhancement_method" class="select">
                        <option value="COMPOUND" @selected($defaults['method'] === 'COMPOUND')>Compound — base × (1.08)ⁿ</option>
                        <option value="SIMPLE" @selected($defaults['method'] === 'SIMPLE')>Simple — base × (1 + 0.08n)</option>
                    </select>
                    <p class="hint">
                        Clause 11(ii) does not say which applies. Over 24 years compound gives about
                        6.34× the base and simple about 2.92×. The method is recorded on every
                        schedule generated from this round.
                    </p>
                </div>

                <button class="btn btn-primary" type="submit">Open round</button>
            </form>
        </div>
    </div>
@else

    {{-- ---------- Round header ---------- --}}
    <div class="tiles">
        <div class="tile">
            <div class="tile-label">Round</div>
            <div class="tile-value">{{ $round->round_no }}</div>
            <div class="tile-sub">{{ ucfirst(strtolower(str_replace('_', ' ', $round->round_type))) }} &middot; {{ $round->status }}</div>
        </div>
        <div class="tile">
            <div class="tile-label">Anchor</div>
            <div class="tile-value" style="font-size:1.15rem">
                {{ \Illuminate\Support\Carbon::parse($round->effective_from)->format('d-m-Y') }}
            </div>
            <div class="tile-sub">Base {{ \Illuminate\Support\Carbon::parse($round->base_date)->format('d-m-Y') }}</div>
        </div>
        <div class="tile is-gold">
            <div class="tile-label">Enhancement</div>
            <div class="tile-value" style="font-size:1.15rem">{{ rtrim(rtrim($round->enhancement_rate, '0'), '.') }}% p.a.</div>
            <div class="tile-sub">{{ ucfirst(strtolower($round->enhancement_method)) }} &middot; {{ $round->reassessment_cycle_years }}-year cycle</div>
        </div>
        @if ($round->determined_monthly_rent)
            <div class="tile">
                <div class="tile-label">Rent determined</div>
                <div class="tile-value">Rs. {{ number_format((float) $round->determined_monthly_rent, 0) }}</div>
                <div class="tile-sub">per month, at the anchor year</div>
            </div>
        @elseif ($round->proposed_monthly_rent)
            <div class="tile is-warn">
                <div class="tile-label">Rent proposed</div>
                <div class="tile-value">Rs. {{ number_format((float) $round->proposed_monthly_rent, 0) }}</div>
                <div class="tile-sub">not yet fixed</div>
            </div>
        @endif
    </div>

    <div class="grid-2" style="gap:1.15rem;align-items:start">
        <div>
            {{-- ---------- Rate inputs ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>Evidence of value</h3>
                    <span class="badge badge-neutral">{{ $round->rateInputs->count() }}</span>
                </div>

                @if ($round->rateInputs->isEmpty())
                    <div class="empty">
                        @include('partials.icon', ['name' => 'scale'])
                        <p class="mb-0">No rate inputs recorded.</p>
                        <p class="hint">The determination rests on these; record what was relied on.</p>
                    </div>
                @else
                    <div class="table-wrap" style="border:0;border-radius:0">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>Source</th><th class="num">Rate</th><th>Basis</th><th>Reference</th><th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($round->rateInputs as $in)
                                <tr>
                                    <td>
                                        {{ $in->rateSource?->name }}
                                        @if ($in->rateSource?->is_operative)
                                            <span class="badge badge-gold">Operative</span>
                                        @endif
                                    </td>
                                    <td class="num">Rs. {{ number_format((float) $in->rate_value, 2) }}</td>
                                    <td class="nowrap faint">{{ $rateUnits[$in->rate_unit] ?? $in->rate_unit }}</td>
                                    <td class="faint" style="font-size:.8rem">
                                        {{ $in->notification_no ?: $in->report_no ?: '—' }}
                                        @if ($in->notification_date)
                                            <div>{{ \Illuminate\Support\Carbon::parse($in->notification_date)->format('d-m-Y') }}</div>
                                        @endif
                                        @if ($in->valuator_name)<div>{{ $in->valuator_name }}</div>@endif
                                    </td>
                                    <td class="text-end">
                                        @if (! $decided)
                                            <form method="POST" action="{{ route('assessment.rates.destroy', $in) }}">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
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
                                        <label for="rate_source_id">Source</label>
                                        <select id="rate_source_id" name="rate_source_id" class="select" required>
                                            @foreach ($rateSources as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="rate_value">Rate (Rs.)</label>
                                        <input type="text" id="rate_value" name="rate_value" class="input"
                                               inputmode="decimal" required>
                                    </div>
                                    <div class="field">
                                        <label for="rate_unit">Basis</label>
                                        <select id="rate_unit" name="rate_unit" class="select">
                                            @foreach ($rateUnits as $v => $l)
                                                <option value="{{ $v }}">{{ $l }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="notification_no">Notification / report no.</label>
                                        <input type="text" id="notification_no" name="notification_no" class="input">
                                    </div>
                                    <div class="field">
                                        <label for="notification_date">Dated</label>
                                        <input type="date" id="notification_date" name="notification_date" class="input">
                                    </div>
                                    <div class="field">
                                        <label for="valuator_name">Valuator (if any)</label>
                                        <input type="text" id="valuator_name" name="valuator_name" class="input">
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" type="submit">
                                    @include('partials.icon', ['name' => 'plus']) Add rate input
                                </button>
                            </form>
                        </div>
                    @endunless
                @endcan
            </div>

            {{-- ---------- Comparables ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>Adjoining properties in similar circumstances</h3>
                    <div class="card-actions"><span class="clause">Clause 2(i)(l)</span></div>
                </div>

                @if ($round->comparables->isEmpty())
                    <div class="empty">
                        @include('partials.icon', ['name' => 'map'])
                        <p class="mb-0">No comparables recorded.</p>
                    </div>
                @else
                    <div class="table-wrap" style="border:0;border-radius:0">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>Property</th><th class="num">Area</th><th class="num">Monthly rent</th>
                                <th class="num">Rs./sqft</th><th>Source</th><th></th>
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
                                                <button class="btn btn-ghost btn-sm" type="submit">Remove</button>
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
                                        <label for="property_description">Property</label>
                                        <input type="text" id="property_description" name="property_description"
                                               class="input" required>
                                    </div>
                                    <div class="field">
                                        <label for="c_area">Area (sqft)</label>
                                        <input type="text" id="c_area" name="area_sqft" class="input" inputmode="decimal">
                                    </div>
                                    <div class="field">
                                        <label for="c_rent">Monthly rent (Rs.)</label>
                                        <input type="text" id="c_rent" name="monthly_rent" class="input"
                                               inputmode="decimal" required>
                                    </div>
                                    <div class="field">
                                        <label for="c_usage">Usage</label>
                                        <select id="c_usage" name="usage_type" class="select">
                                            <option value="RESIDENTIAL">Residential</option>
                                            <option value="COMMERCIAL">Commercial</option>
                                            <option value="RESIDENTIAL_CUM_COMMERCIAL">Residential-cum-commercial</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="c_distance">Distance (m)</label>
                                        <input type="text" id="c_distance" name="distance_meters" class="input" inputmode="decimal">
                                    </div>
                                    <div class="field">
                                        <label for="c_source">Source of information</label>
                                        <input type="text" id="c_source" name="information_source" class="input">
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" type="submit">
                                    @include('partials.icon', ['name' => 'plus']) Add comparable
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
                        <h3>Year-by-year schedule</h3>
                        <span class="badge badge-neutral">{{ $schedule->count() }} years</span>
                        <div class="card-actions">
                            <a href="{{ route('arrears.index', $application) }}" class="btn btn-outline btn-sm">
                                Arrears ledger &rarr;
                            </a>
                        </div>
                    </div>
                    <div class="table-wrap" style="border:0;border-radius:0;max-height:460px;overflow-y:auto">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>Rent year</th><th>Period</th>
                                <th class="num">Monthly</th><th class="num">Months</th><th class="num">Annual</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($schedule as $s)
                                <tr class="{{ $s->is_milestone_year ? 'is-milestone' : '' }}">
                                    <td>
                                        <strong>{{ $s->year }}</strong>
                                        @if ($s->is_reassessment_year)
                                            <span class="badge badge-info" title="Clause 11(i)">Re-assessment</span>
                                        @endif
                                    </td>
                                    <td class="nowrap faint" style="font-size:.8rem">
                                        {{ \Illuminate\Support\Carbon::parse($s->period_from)->format('d-m-Y') }}
                                        &ndash;
                                        {{ \Illuminate\Support\Carbon::parse($s->period_to)->format('d-m-Y') }}
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
                            A rent year runs 1 July to 30 June, because both statutory anchors —
                            01-07-2000 for arrears and 01-07-2006 for assessment — fall on 1 July.
                            Highlighted rows are the milestone years reported to higher authorities.
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
                            <h3>Proposed assessment</h3>
                            <div class="card-actions"><span class="clause">Clause 10(i)(a)–(b)</span></div>
                        </div>
                        <div class="card-body">
                            <p class="muted" style="font-size:.86rem">
                                The proposal is what goes on public display and into the notice.
                                It is not the fixation.
                            </p>
                            <form method="POST" action="{{ route('assessment.propose', $round) }}">
                                @csrf
                                <div class="field">
                                    <label for="proposed_monthly_rent">Proposed monthly rent (Rs.)</label>
                                    <input type="text" id="proposed_monthly_rent" name="proposed_monthly_rent"
                                           class="input" inputmode="decimal" required
                                           value="{{ $round->proposed_monthly_rent }}">
                                </div>
                                <button class="btn btn-primary" type="submit" style="width:100%">
                                    Record proposal
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
                        <h3>{{ $decided ? 'Determination' : 'Fix the rent' }}</h3>
                        <div class="card-actions"><span class="clause">Clause 10(i)(d)</span></div>
                    </div>
                    <div class="card-body">
                        @if ($decided && $decision)
                            <dl class="kv">
                                <dt>Rent fixed</dt>
                                <dd><strong>Rs. {{ number_format((float) $decision->determined_monthly_rent, 2) }}</strong> per month</dd>
                                <dt>Decided</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($decision->decided_at)->format('d-m-Y H:i') }}</dd>
                            </dl>
                            <h4 class="mt-2">Reasons recorded</h4>
                            <p class="muted" style="white-space:pre-wrap;font-size:.87rem">{{ $decision->reasons }}</p>
                            @if ($decision->objections_considered)
                                <h4>Objections considered</h4>
                                <p class="muted" style="white-space:pre-wrap;font-size:.87rem">{{ $decision->objections_considered }}</p>
                            @endif
                        @else
                            <div class="alert alert-warn">
                                @include('partials.icon', ['name' => 'alert'])
                                <div>
                                    <p class="mb-0">
                                        Fixing the rent generates the schedule back to
                                        {{ $application->possession ? \Illuminate\Support\Carbon::parse($application->possession->arrears_from)->format('Y') : 'the arrears start' }}
                                        and builds the arrears ledger. Reasons are mandatory and become part of the record.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('assessment.determine', $round) }}" id="determineForm">
                                @csrf
                                <div class="field">
                                    <label for="determined_monthly_rent">Monthly rent determined (Rs.) <span class="req">*</span></label>
                                    <input type="text" id="determined_monthly_rent" name="determined_monthly_rent"
                                           class="input" inputmode="decimal" required
                                           value="{{ old('determined_monthly_rent', $round->proposed_monthly_rent) }}">
                                </div>

                                @if ($areaSqft)
                                    <div class="field">
                                        <label for="rate_per_sqft">Equivalent rate per sqft</label>
                                        <input type="text" id="rate_per_sqft" name="rate_per_sqft" class="input"
                                               inputmode="decimal" readonly>
                                        <p class="hint">Derived from {{ number_format((float) $areaSqft, 2) }} sqft.</p>
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
                                    <label for="reasons">Reasons <span class="req">*</span></label>
                                    <textarea id="reasons" name="reasons" class="textarea" style="min-height:150px"
                                              required minlength="40">{{ old('reasons') }}</textarea>
                                    <p class="hint">
                                        Set out what was considered — the rates relied on, the comparables, the
                                        objections — and why this figure follows.
                                    </p>
                                </div>

                                <div class="field">
                                    <label for="objections_considered">Objections considered</label>
                                    <textarea id="objections_considered" name="objections_considered"
                                              class="textarea" style="min-height:80px">{{ old('objections_considered') }}</textarea>
                                </div>

                                <button class="btn btn-primary btn-lg" type="submit" style="width:100%">
                                    @include('partials.icon', ['name' => 'check']) Fix rent and build ledger
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endcan

            {{-- ---------- Milestone grid ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>Milestone years</h3>
                    <div class="card-actions"><span class="clause">Report grid</span></div>
                </div>
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead><tr><th>Year</th><th class="num">Monthly</th><th class="num">Annual</th></tr></thead>
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
                <div class="card-head"><h3>Next steps</h3></div>
                <div class="card-body">
                    <div class="btn-row">
                        <a href="{{ route('due-process.index', $application) }}" class="btn btn-outline">
                            @include('partials.icon', ['name' => 'inbox']) Notices &amp; objections
                        </a>
                        <a href="{{ route('arrears.index', $application) }}" class="btn btn-outline">
                            @include('partials.icon', ['name' => 'cash']) Arrears
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
                total.textContent = 'Approximately Rs. ' + d.total + ' in total rent across ' + d.years + ' year(s)';
                meta.textContent  = d.rate + '% per annum, ' + d.method.toLowerCase() +
                                    ', anchored at rent year ' + d.anchor_year;
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
