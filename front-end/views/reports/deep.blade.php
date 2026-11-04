@extends('layouts.app')

@section('title', __('Deep report'))
@section('heading', __('Deep report') . ' — ' . $application->application_no)

@section('content')

@php
    $a = $application;
    $p = $a->property;
    $area = $p?->currentArea;
    $poss = $a->possession;
    $trace = $area ? (is_array($area->conversion_trace) ? $area->conversion_trace
                     : json_decode((string) $area->conversion_trace, true)) : null;
    $fmt = fn ($d, $f = 'd-m-Y') => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat($f) : '—';
    $rs  = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
    $sec = 0;
@endphp

<div class="card no-print">
    <div class="card-body tight">
        <div class="btn-row mb-2">
            <a href="{{ route('applications.show', $a) }}" class="btn btn-ghost btn-sm">&larr; {{ __('Case file') }}</a>
        </div>
        @include('partials.report-formats', ['route' => route('reports.deep', $a)])
    </div>
</div>

{{-- ============ Cover ============ --}}
<div class="card">
    <div class="card-body" style="text-align:center;padding:2rem 1.15rem">
        <div class="brand-mark" style="margin:0 auto .75rem">
            @include('partials.icon', ['name' => 'shield'])
        </div>
        <h1 style="margin-bottom:.2rem">{{ __('Evacuee Trust Property Board') }}</h1>
        <p class="muted" style="margin-bottom:1.25rem">
            {{ __('Regularization of Possession') }} &mdash;
            {{ __('Clause 3(ii), Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977') }}
        </p>

        <h2 style="margin-bottom:.35rem">{{ $a->application_no }}</h2>
        <p class="lede" style="margin-bottom:1rem">
            {{ $a->applicant?->nameWithParentage(true) }}<br>
            {{ $p?->identity(true) }}, {{ __($p?->district?->name ?? '') }}
        </p>

        <div class="inline-list" style="justify-content:center">
            <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">
                {{ __('Payment :status', ['status' => __($a->payment_status ?? '')]) }}
            </span>
            <span class="badge badge-{{ $a->statusTone() }}">{{ __($a->statusLabel()) }}</span>
            @if ($a->is_sub_judice)<span class="badge badge-danger">{{ __('Sub judice') }}</span>@endif
        </div>

        <p class="faint" style="font-size:.8rem;margin-top:1.25rem;margin-bottom:0">
            {{ __('Complete case file, every element included.') }}<br>
            {{ __('Generated :date by :name (:role).', [
                'date' => $generatedAt->translatedFormat('d F Y, H:i'),
                'name' => __($generatedBy->name),
                'role' => __($generatedBy->primaryRole()?->name ?? ''),
            ]) }}
        </p>
    </div>
</div>

{{-- ============ 1. Applicant ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Applicant particulars') }}</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>{{ __('Name') }}</dt><dd>{{ $a->applicant?->full_name }}</dd>
            <dt>{{ $a->applicant?->parentage_type === 'HUSBAND' ? __('Husband') : __('Father') }}</dt>
            <dd>{{ $a->applicant?->parentage_name }}</dd>
            <dt>{{ __('CNIC') }}</dt><dd class="num">{{ $a->applicant?->formattedCnic() }}</dd>
            <dt>{{ __('Contact') }}</dt><dd>{{ $a->applicant?->contact }}</dd>
            @if ($a->applicant?->email)<dt>{{ __('Email') }}</dt><dd>{{ $a->applicant->email }}</dd>@endif
            <dt>{{ __('Address') }}</dt><dd>{{ $a->applicant?->postal_address }}</dd>
        </dl>
    </div>
</div>

{{-- ============ 2. Property ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Property particulars') }}</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>{{ __('Property no.') }}</dt><dd>{{ $p?->property_no }}</dd>
            <dt>{{ __('Sub-unit no.') }}</dt><dd>{{ $p?->sub_unit_no ?: '—' }}</dd>
            <dt>{{ __('Type') }}</dt><dd>{{ __($p?->typeLabel() ?? '') }}</dd>
            <dt>{{ __('Usage') }}</dt><dd>{{ __($p?->usageLabel() ?? '') }}</dd>
            <dt>{{ __('Address') }}</dt><dd>{{ $p?->address }}</dd>
            <dt>{{ __('Mouza') }}</dt><dd>{{ $p?->mouza?->name ?: '—' }}</dd>
            <dt>{{ __('City') }}</dt><dd>{{ $p?->city ?: '—' }}</dd>
            <dt>{{ __('Tehsil') }}</dt><dd>{{ __($p?->tehsil?->name ?: '—') }}</dd>
            <dt>{{ __('District') }}</dt><dd>{{ __($p?->district?->name ?? '') }}</dd>
            <dt>{{ __('Province') }}</dt><dd>{{ __($p?->province?->name ?? '') }}</dd>
            <dt>{{ __('Khewat / Khatooni / Khasra') }}</dt>
            <dd>{{ $p?->khewat_no ?: '—' }} / {{ $p?->khatooni_no ?: '—' }} / {{ $p?->khasra_no ?: '—' }}</dd>
        </dl>
    </div>
</div>

{{-- ============ 3. Area ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Area, and how it was converted') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Pakistani revenue measure') }}</span></div>
    </div>
    <div class="card-body">
        @if ($area)
            <dl class="kv">
                <dt>{{ __('Area as entered') }}</dt>
                <dd>
                    @if ($trace && ! empty($trace['components']))
                        {{ collect($trace['components'])
                            ->map(fn ($c) => rtrim(rtrim($c['quantity'], '0'), '.') . ' ' . __($c['unit_name']))
                            ->implode(' + ') }}
                    @else
                        {{ $area->entered_value }} {{ $area->entered_unit_code }}
                    @endif
                </dd>
                <dt>{{ __('Standard applied') }}</dt><dd>{{ __($a->unitProfile?->name ?? '') }}</dd>
                <dt>{{ __('Area in square feet') }}</dt>
                <dd><strong>{{ __(':area sqft', ['area' => number_format((float) $area->area_sqft, 4)]) }}</strong></dd>
                @if ($area->covered_area_sqft)
                    <dt>{{ __('Covered area') }}</dt><dd>{{ __(':area sqft', ['area' => number_format((float) $area->covered_area_sqft, 2)]) }}</dd>
                @endif
            </dl>

            @if ($trace && ! empty($trace['components']))
                <h4 class="mt-2">{{ __('Worked conversion') }}</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                        <tr><th>{{ __('Component') }}</th><th class="num">{{ __('Quantity') }}</th><th class="num">{{ __('sqft per unit') }}</th><th class="num">{{ __('Subtotal') }}</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($trace['components'] as $c)
                            <tr>
                                <td>{{ __($c['unit_name']) }}</td>
                                <td class="num">{{ rtrim(rtrim($c['quantity'], '0'), '.') }}</td>
                                <td class="num">{{ rtrim(rtrim($c['sqft_per_unit'], '0'), '.') }}</td>
                                <td class="num">{{ number_format((float) $c['subtotal_sqft'], 4) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr style="background:var(--tint);font-weight:650">
                            <td colspan="3">{{ __('Total') }}</td>
                            <td class="num">{{ number_format((float) $area->area_sqft, 4) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="hint mt-1">
                    {{ __('The factors above were frozen against this application when the area was recorded, so a later change to the conversion table cannot restate it.') }}
                </p>
            @endif
        @else
            <p class="muted mb-0">{{ __('No area recorded.') }}</p>
        @endif
    </div>
</div>

{{-- ============ 4. Location and geo-tag ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Location and geo-tagging') }}</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>{{ __('Location') }}</dt><dd>{{ $p?->locationChain() }}</dd>
        </dl>
        @if ($p?->geoTags->isNotEmpty())
            <div class="table-wrap mt-1">
                <table class="data">
                    <thead><tr><th class="num">{{ __('Latitude') }}</th><th class="num">{{ __('Longitude') }}</th><th>{{ __('Source') }}</th><th>{{ __('Captured') }}</th></tr></thead>
                    <tbody>
                    @foreach ($p->geoTags as $g)
                        <tr>
                            <td class="num">{{ $g->latitude }}</td>
                            <td class="num">{{ $g->longitude }}</td>
                            <td>{{ __(ucwords(strtolower(str_replace('_', ' ', $g->source)))) }}</td>
                            <td class="nowrap">{{ $fmt($g->captured_at) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted mb-0">{{ __('No geo coordinates recorded.') }}</p>
        @endif
    </div>
</div>

{{-- ============ 5. Possession ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Possession and eligibility') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(a)–(b)') }}</span></div>
    </div>
    <div class="card-body">
        @if ($poss)
            <dl class="kv">
                <dt>{{ __('Date of possession') }}</dt><dd><strong>{{ $fmt($poss->date_of_possession) }}</strong></dd>
                <dt>{{ __('Nature') }}</dt><dd>{{ __(ucfirst(strtolower($poss->possession_nature))) }}</dd>
                <dt>{{ __('Cut-off applied') }}</dt><dd>{{ $fmt($poss->cutoff_applied) }}</dd>
                <dt>{{ __('Eligible') }}</dt>
                <dd>
                    <span class="badge badge-{{ $poss->is_eligible ? 'good' : 'danger' }}">
                        {{ $poss->is_eligible ? __('Yes') : __('No') }}
                    </span>
                </dd>
                <dt>{{ __('Judicial verdict') }}</dt><dd>{{ $fmt($poss->date_of_judicial_verdict) }}</dd>
                @if ($poss->court_name)<dt>{{ __('Name of Court') }}</dt><dd>{{ $poss->court_name }}</dd>@endif
                @if ($poss->judicial_reference)<dt>{{ __('Case reference') }}</dt><dd>{{ $poss->judicial_reference }}</dd>@endif
                @if ($poss->judgment_gist)<dt>{{ __('Gist of Decision') }}</dt><dd>{{ $poss->judgment_gist }}</dd>@endif
                <dt>{{ __('Arrears run from') }}</dt>
                <dd>
                    <strong>{{ $fmt($poss->arrears_from) }}</strong>
                    <span class="badge badge-neutral">
                        {{ __(ucwords(strtolower(str_replace('_', ' ', $poss->arrears_from_basis)))) }}
                    </span>
                </dd>
            </dl>
            <div class="alert {{ $poss->is_eligible ? 'alert-good' : 'alert-danger' }} mt-2">
                @include('partials.icon', ['name' => $poss->is_eligible ? 'check' : 'alert'])
                <div><p class="mb-0">{{ app(\App\Services\EligibilityService::class)->describe($poss->eligibility_reason, (bool) $poss->is_eligible) }}</p></div>
            </div>
            @if ($poss->possession_description)
                <h4>{{ __('How possession was taken') }}</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $poss->possession_description }}</p>
            @endif
        @else
            <p class="muted mb-0">{{ __('No possession details recorded.') }}</p>
        @endif
    </div>
</div>

{{-- ============ 6. Evidence ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Schedule of evidence') }}</h3>
        <span class="badge badge-neutral">{{ $a->documents->count() }}</span>
        <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(c)') }}</span></div>
    </div>
    @if ($a->documents->isEmpty())
        <div class="card-body"><p class="muted mb-0">{{ __('No document filed.') }}</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>{{ __('Head') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Dated') }}</th><th>{{ __('Issuing authority') }}</th>
                    <th>{{ __('Certified') }}</th><th>{{ __('Status') }}</th><th>{{ __('Verified by') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($a->documents as $d)
                    <tr>
                        <td>{{ __($d->documentType?->name ?? '') }}
                            <div class="faint" style="font-size:.72rem;font-family:var(--font-mono)">
                                {{ substr($d->sha256, 0, 20) }}…
                            </div>
                        </td>
                        <td>{{ $d->reference_no ?: '—' }}</td>
                        <td class="nowrap">{{ $fmt($d->document_date) }}</td>
                        <td>{{ $d->issuing_authority ?: '—' }}</td>
                        <td>{{ $d->is_certified_copy ? __('Yes') : __('No') }}</td>
                        <td><span class="badge badge-{{ $d->status === 'VERIFIED' ? 'good' : ($d->status === 'WAIVED' ? 'gold' : 'neutral') }}">
                            {{ __(ucfirst(strtolower($d->status))) }}</span></td>
                        <td class="faint" style="font-size:.78rem">
                            {{ $fmt($d->verified_at) }}
                            @if ($d->verification_remarks)
                                <div>{{ $d->verification_remarks }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 7. Rent assessment ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Rent assessment') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Clause 10') }}</span></div>
    </div>
    <div class="card-body">
        @if ($round)
            <dl class="kv">
                <dt>{{ __('Round') }}</dt><dd>{{ $round->round_no }} ({{ __(ucfirst(strtolower($round->round_type))) }})</dd>
                <dt>{{ __('Base date') }}</dt><dd>{{ $fmt($round->base_date) }}</dd>
                <dt>{{ __('Anchor date') }}</dt><dd>{{ $fmt($round->effective_from) }}</dd>
                <dt>{{ __('Enhancement') }}</dt>
                <dd>{{ __(':rate% per annum, :method', [
                        'rate' => rtrim(rtrim($round->enhancement_rate, '0'), '.'),
                        'method' => __(strtolower($round->enhancement_method)),
                    ]) }} &mdash;
                    <span class="clause">{{ __('Clause 11(ii)') }}</span></dd>
                <dt>{{ __('Re-assessment cycle') }}</dt><dd>{{ __(':count years', ['count' => $round->reassessment_cycle_years]) }} &mdash;
                    <span class="clause">{{ __('Clause 11(i)') }}</span></dd>
                <dt>{{ __('Rent proposed') }}</dt><dd>{{ $round->proposed_monthly_rent ? $rs($round->proposed_monthly_rent) : '—' }}</dd>
                <dt>{{ __('Rent determined') }}</dt>
                <dd><strong>{{ $round->determined_monthly_rent ? $rs($round->determined_monthly_rent) : '—' }}</strong></dd>
            </dl>

            @if ($round->rateInputs->isNotEmpty())
                <h4 class="mt-2">{{ __('Evidence of value relied on') }}</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>{{ __('Source') }}</th><th class="num">{{ __('Rate') }}</th><th>{{ __('Basis') }}</th><th>{{ __('Reference') }}</th></tr></thead>
                        <tbody>
                        @foreach ($round->rateInputs as $in)
                            <tr>
                                <td>{{ __($in->rateSource?->name ?? '') }}</td>
                                <td class="num">{{ $rs($in->rate_value) }}</td>
                                <td class="faint">{{ __(strtolower(str_replace('_', ' ', $in->rate_unit))) }}</td>
                                <td class="faint" style="font-size:.8rem">
                                    {{ $in->notification_no ?: $in->report_no ?: '—' }}
                                    @if ($in->notification_date) &middot; {{ $fmt($in->notification_date) }} @endif
                                    @if ($in->valuator_name) &middot; {{ $in->valuator_name }} @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($round->comparables->isNotEmpty())
                <h4 class="mt-2">{{ __('Prevailing market rent of adjoining properties') }}</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>{{ __('Property') }}</th><th class="num">{{ __('Area') }}</th><th class="num">{{ __('Rent') }}</th><th class="num">{{ __('Rs./sqft') }}</th><th>{{ __('Source') }}</th></tr></thead>
                        <tbody>
                        @foreach ($round->comparables as $c)
                            <tr>
                                <td>{{ $c->property_description }}</td>
                                <td class="num">{{ $c->area_sqft ? number_format((float) $c->area_sqft, 0) : '—' }}</td>
                                <td class="num">{{ $rs($c->monthly_rent) }}</td>
                                <td class="num">
                                    @if ($c->area_sqft && (float) $c->area_sqft > 0)
                                        {{ number_format((float) $c->monthly_rent / (float) $c->area_sqft, 2) }}
                                    @else — @endif
                                </td>
                                <td class="faint" style="font-size:.8rem">{{ $c->information_source ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @foreach ($decisions as $dec)
                <div class="alert {{ $dec->is_superseded ? 'alert-warn' : 'alert-good' }} mt-2">
                    @include('partials.icon', ['name' => 'scale'])
                    <div>
                        <strong>
                            {{ __('Rent fixed at :amount per month', ['amount' => $rs($dec->determined_monthly_rent)]) }}
                            @if ($dec->is_superseded) {{ __('(superseded)') }} @endif
                        </strong>
                        <div class="faint" style="font-size:.8rem">
                            {{ __('Decided :date', ['date' => $fmt($dec->decided_at, 'd-m-Y H:i')]) }}
                        </div>
                        <p style="white-space:pre-wrap;margin:.5rem 0 0">{{ $dec->reasons }}</p>
                        @if ($dec->objections_considered)
                            <p style="white-space:pre-wrap;margin:.5rem 0 0"><em>{{ $dec->objections_considered }}</em></p>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <p class="muted mb-0">{{ __('No assessment has been opened.') }}</p>
        @endif
    </div>
</div>

{{-- ============ 8. Rent table ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Rent, in the milestone years') }}</h3>
        <div class="card-actions"><span class="clause">2000 · 2004 · 2008 · 2012 · 2016 · 2020 · 2024</span></div>
    </div>
    <div class="table-wrap" style="border:0;border-radius:0">
        <table class="data">
            <thead><tr><th>{{ __('Rent year') }}</th><th>{{ __('Period') }}</th><th class="num">{{ __('Monthly rent') }}</th><th class="num">{{ __('Annual rent') }}</th></tr></thead>
            <tbody>
            @foreach ($milestones as $m)
                <tr class="{{ $m['in_scope'] ? 'is-milestone' : 'row-muted' }}">
                    <td><strong>{{ $m['year'] }}</strong></td>
                    <td class="nowrap">{{ str_replace(' to ', ' ' . __('to') . ' ', $m['period']) }}</td>
                    <td class="num">{{ $m['monthly_rent'] ? $rs($m['monthly_rent']) : '—' }}</td>
                    <td class="num">{{ $m['annual_rent'] ? $rs($m['annual_rent']) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ============ 9. Objections ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Objectors, their pleas, and the decisions') }}</h3>
        <span class="badge badge-neutral">{{ $a->objections->count() }}</span>
    </div>
    <div class="card-body">
        @forelse ($a->objections as $o)
            @php $d = $objectionDecisions->get($o->id); @endphp
            <div class="inline-list">
                <strong>{{ $o->objector_name }}</strong>
                @if ($o->objector_parentage)<span class="faint">{{ __('s/o') }} {{ $o->objector_parentage }}</span>@endif
                <span class="badge badge-{{ $o->status === 'DECIDED' ? 'good' : 'warn' }}">{{ __(ucfirst(strtolower($o->status))) }}</span>
                @unless ($o->is_within_time)<span class="badge badge-danger">{{ __('Out of time') }}</span>@endunless
            </div>
            <dl class="kv mt-1" style="font-size:.84rem">
                <dt>{{ __('CNIC') }}</dt><dd class="num">{{ $o->objector_cnic ?: '—' }}</dd>
                <dt>{{ __('Contact') }}</dt><dd>{{ $o->objector_contact ?: '—' }}</dd>
                <dt>{{ __('Relationship') }}</dt><dd>{{ $o->relationship_to_property ?: '—' }}</dd>
                <dt>{{ __('Filed on') }}</dt><dd>{{ $fmt($o->filed_on) }}</dd>
            </dl>
            <h4 style="margin-top:.6rem">{{ __('Plea') }}</h4>
            <p class="muted" style="white-space:pre-wrap">{{ $o->plea }}</p>
            @if ($d)
                <h4>{{ __('Decision') }} &mdash; {{ __(ucwords(strtolower(str_replace('_', ' ', $d->decision)))) }}</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $d->reasons }}</p>
            @endif
            @if (! $loop->last)<hr class="divider">@endif
        @empty
            <p class="muted mb-0">{{ __('No objection was filed.') }}</p>
        @endforelse
    </div>
</div>

{{-- ============ 10. Notices and hearings ============ ---}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Notices and hearings') }}</h3></div>
    <div class="card-body">
        @if ($a->notices->isNotEmpty())
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>{{ __('Notice') }}</th><th>{{ __('Type') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Served') }}</th><th>{{ __('Mode') }}</th><th>{{ __('Objections until') }}</th></tr></thead>
                    <tbody>
                    @foreach ($a->notices as $n)
                        <tr>
                            <td style="font-size:.8rem">{{ $n->notice_no }}</td>
                            <td>{{ __(ucfirst(strtolower($n->notice_type))) }}</td>
                            <td class="nowrap">{{ $fmt($n->issued_on) }}</td>
                            <td class="nowrap">{{ $fmt($n->served_on) }}</td>
                            <td class="faint">{{ __(ucwords(strtolower(str_replace('_', ' ', $n->service_mode)))) }}</td>
                            <td class="nowrap">{{ $fmt($n->objection_deadline) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted">{{ __('No notice issued.') }}</p>
        @endif

        @foreach ($a->hearings as $h)
            <h4 class="mt-2">{{ __('Hearing') }} {{ $fmt($h->scheduled_for, 'd-m-Y H:i') }} &mdash; {{ __(ucfirst(strtolower($h->status))) }}</h4>
            @if ($h->venue)<p class="faint" style="font-size:.82rem;margin-bottom:.3rem">{{ $h->venue }}</p>@endif
            @if ($h->proceedings)
                <p class="muted" style="white-space:pre-wrap">{{ $h->proceedings }}</p>
            @endif
        @endforeach
    </div>
</div>

{{-- ============ 11. Occupant offers ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Rent offered by other occupants') }}</h3></div>
    @if ($a->occupantOffers->isEmpty())
        <div class="card-body"><p class="muted mb-0">{{ __('No competing offer recorded.') }}</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>{{ __('Occupant') }}</th><th>{{ __('CNIC') }}</th><th>{{ __('Portion') }}</th><th class="num">{{ __('Rent offered') }}</th><th>{{ __('Offered on') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                @foreach ($a->occupantOffers as $o)
                    <tr>
                        <td>{{ $o->occupant_name }}</td>
                        <td class="num">{{ $o->occupant_cnic ?: '—' }}</td>
                        <td>{{ $o->portion_occupied ?: '—' }}</td>
                        <td class="num">{{ $rs($o->rent_offered) }}</td>
                        <td class="nowrap">{{ $fmt($o->offer_date) }}</td>
                        <td>{{ __(ucwords(strtolower(str_replace('_', ' ', $o->status)))) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 12. Litigation ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Litigation') }}</h3></div>
    @if ($a->litigations->isEmpty())
        <div class="card-body"><p class="muted mb-0">{{ __('No case on record.') }}</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>{{ __('Court') }}</th><th>{{ __('Case no.') }}</th><th>{{ __('Type') }}</th><th>{{ __('Pending') }}</th><th>{{ __('Restraining order') }}</th><th>{{ __('Direction case') }}</th><th>{{ __('Outcome') }}</th></tr></thead>
                <tbody>
                @foreach ($a->litigations as $l)
                    <tr>
                        <td>{{ $l->court_name }}</td>
                        <td>{{ $l->case_no }}</td>
                        <td>{{ __(ucwords(strtolower(str_replace('_', ' ', $l->case_type)))) }}</td>
                        <td>{{ $l->is_pending ? __('Yes') : __('No') }}</td>
                        <td>{{ $l->has_restraining_order ? __('Yes') . ' — ' . $fmt($l->restraining_order_date) : __('No') }}</td>
                        <td>{{ $l->is_direction_case ? __('Yes') : __('No') }}</td>
                        <td>{{ __(ucfirst(strtolower($l->outcome))) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 13. Fee ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Processing fee') }}</h3>
        <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">{{ __($a->payment_status ?? '') }}</span>
    </div>
    @if ($a->feePayments->isEmpty())
        <div class="card-body"><p class="muted mb-0">{{ __('No deposit recorded.') }}</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>{{ __('Instrument') }}</th><th>{{ __('No.') }}</th><th>{{ __('Dated') }}</th><th class="num">{{ __('Amount') }}</th><th>{{ __('Bank / branch') }}</th><th>{{ __('Depositor') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                @foreach ($a->feePayments as $f)
                    <tr>
                        <td>{{ __(ucwords(strtolower(str_replace('_', ' ', $f->instrument_type)))) }}</td>
                        <td>{{ $f->instrument_no }}</td>
                        <td class="nowrap">{{ $fmt($f->instrument_date) }}</td>
                        <td class="num">{{ $rs($f->amount) }}</td>
                        <td>
                            {{ $f->bank_name }}
                            <div class="faint" style="font-size:.78rem">
                                {{ $f->branch_name }}@if ($f->branch_code) · {{ $f->branch_code }}@endif
                            </div>
                        </td>
                        <td>
                            {{ $f->depositor_name }}
                            <div class="faint num" style="font-size:.75rem">{{ $f->depositor_cnic }}</div>
                        </td>
                        <td><span class="badge badge-{{ $f->status === 'VERIFIED' ? 'good' : 'warn' }}">{{ __(ucfirst(strtolower($f->status))) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 14. Arrears ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Arrears ledger') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(b)') }}</span></div>
    </div>
    <div class="card-body">
        <dl class="kv">
            <dt>{{ __('Assessed') }}</dt><dd>{{ $rs($arrears['total_due']) }}</dd>
            <dt>{{ __('Recovered') }}</dt><dd>{{ $rs($arrears['total_paid']) }}</dd>
            <dt>{{ __('Remitted') }}</dt><dd>{{ $rs($arrears['total_remitted']) }}</dd>
            <dt>{{ __('Balance') }}</dt><dd><strong>{{ $rs($arrears['balance']) }}</strong></dd>
        </dl>
        <div class="alert {{ $clearance['satisfied'] ? 'alert-good' : 'alert-warn' }} mt-1">
            @include('partials.icon', ['name' => $clearance['satisfied'] ? 'check' : 'alert'])
            <div><p class="mb-0">{{ $clearance['reason'] }}</p></div>
        </div>
    </div>
    @if ($ledger->isNotEmpty())
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>{{ __('Year') }}</th><th>{{ __('Period') }}</th><th class="num">{{ __('Monthly') }}</th><th class="num">{{ __('Months') }}</th><th class="num">{{ __('Due') }}</th><th class="num">{{ __('Paid') }}</th><th class="num">{{ __('Remitted') }}</th><th class="num">{{ __('Balance') }}</th></tr></thead>
                <tbody>
                @foreach ($ledger as $r)
                    <tr>
                        <td>{{ $r->period_year }}</td>
                        <td class="nowrap faint" style="font-size:.78rem">{{ $fmt($r->period_from) }} – {{ $fmt($r->period_to) }}</td>
                        <td class="num">{{ number_format((float) $r->monthly_rent, 2) }}</td>
                        <td class="num">{{ rtrim(rtrim($r->months_applicable, '0'), '.') }}</td>
                        <td class="num">{{ number_format((float) $r->amount_due, 2) }}</td>
                        <td class="num">{{ number_format((float) $r->amount_paid, 2) }}</td>
                        <td class="num">{{ number_format((float) $r->remission_amount, 2) }}</td>
                        <td class="num"><strong>{{ number_format((float) $r->balance, 2) }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 15. Receipts, instalments, remission ============ --}}
@if ($receipts->isNotEmpty() || $instalmentPlans->isNotEmpty() || $remissions->isNotEmpty())
    <div class="card">
        <div class="card-head"><h3>{{ ++$sec }}. {{ __('Recovery') }}</h3></div>
        <div class="card-body">
            @if ($receipts->isNotEmpty())
                <h4>{{ __('Receipts') }}</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Date') }}</th><th>{{ __('Mode') }}</th><th class="num">{{ __('Amount') }}</th></tr></thead>
                        <tbody>
                        @foreach ($receipts as $r)
                            <tr>
                                <td>{{ $r->receipt_no }}</td>
                                <td class="nowrap">{{ $fmt($r->receipt_date) }}</td>
                                <td>{{ __(ucwords(strtolower(str_replace('_', ' ', $r->payment_mode)))) }}</td>
                                <td class="num">{{ $rs($r->amount) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @foreach ($instalmentPlans as $pl)
                <h4 class="mt-2">{{ __('Instalment plan') }} &mdash; <span class="clause">{{ __('Clause 13') }}</span></h4>
                <dl class="kv">
                    <dt>{{ __('Total') }}</dt><dd>{{ $rs($pl->total_amount) }}</dd>
                    <dt>{{ __('Instalments') }}</dt><dd>{{ $pl->instalment_count }} &times; {{ $rs($pl->instalment_amount) }}</dd>
                    <dt>{{ __('Status') }}</dt><dd>{{ __(ucfirst(strtolower($pl->status))) }}</dd>
                </dl>
                @if ($pl->justification)<p class="muted" style="white-space:pre-wrap">{{ $pl->justification }}</p>@endif
            @endforeach

            @foreach ($remissions as $rm)
                <h4 class="mt-2">{{ __('Remission') }} &mdash; <span class="clause">{{ __('Clause 12') }}</span></h4>
                <dl class="kv">
                    <dt>{{ __('Ground') }}</dt><dd>{{ __(ucfirst(strtolower($rm->ground))) }}</dd>
                    <dt>{{ __('Type') }}</dt><dd>{{ __(ucwords(strtolower(str_replace('_', ' ', $rm->remission_type)))) }}</dd>
                    <dt>{{ __('Status') }}</dt><dd>{{ __(ucfirst(strtolower($rm->status))) }}</dd>
                </dl>
                <p class="muted" style="white-space:pre-wrap">{{ $rm->grounds_detail }}</p>
                @if ($rm->approval_reasons)
                    <p class="muted" style="white-space:pre-wrap"><em>{{ $rm->approval_reasons }}</em></p>
                @endif
            @endforeach
        </div>
    </div>
@endif

{{-- ============ 16. Nominee ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Nominee and legal heirs') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Scheme para 3(iii)(B)') }}</span></div>
    </div>
    <div class="card-body">
        @forelse ($a->nominees as $n)
            <dl class="kv">
                <dt>{{ __('Nominee') }}</dt><dd>{{ $n->nominee_name }}</dd>
                <dt>{{ __('Relationship') }}</dt><dd>{{ $n->relationship }}</dd>
                <dt>{{ __('CNIC') }}</dt><dd class="num">{{ $n->nominee_cnic ?: '—' }}</dd>
                <dt>{{ __('Form received') }}</dt><dd>{{ $fmt($n->form_received_on) }}</dd>
            </dl>
            @if ($n->heirs->isNotEmpty())
                <h4>{{ __('Legal heirs') }}</h4>
                <ol style="margin:0;padding-inline-start:1.2rem">
                    @foreach ($n->heirs as $h)
                        <li>{{ $h->heir_name }} &mdash; {{ $h->relationship }}</li>
                    @endforeach
                </ol>
            @endif
        @empty
            <p class="muted mb-0">
                {{ __('No nomination form on record. Under the proviso to Scheme para 3(iii)(B) the District Officer shall not regularize the possession until it is obtained.') }}
            </p>
        @endforelse
    </div>
</div>

{{-- ============ 17. Approvals ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. {{ __('Decisions and approvals') }}</h3>
        <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(d)') }}</span></div>
    </div>
    <div class="card-body">
        @forelse ($a->approvals as $ap)
            <div class="inline-list">
                <strong>{{ __(ucwords(strtolower(str_replace('_', ' ', $ap->level)))) }}</strong>
                <span class="badge badge-{{ $ap->action === 'APPROVE' ? 'good' : ($ap->action === 'REJECT' ? 'danger' : 'warn') }}">
                    {{ __(ucfirst(strtolower($ap->action))) }}
                </span>
                @if (! $ap->is_within_sla)<span class="badge badge-danger">{{ __('Beyond the statutory limit') }}</span>@endif
            </div>
            <dl class="kv mt-1" style="font-size:.84rem">
                <dt>{{ __('Acted on') }}</dt><dd>{{ $fmt($ap->acted_at, 'd-m-Y H:i') }}</dd>
                <dt>{{ __('Due by') }}</dt><dd>{{ $fmt($ap->due_by) }}</dd>
                @if ($ap->days_taken !== null)<dt>{{ __('Days taken') }}</dt><dd>{{ $ap->days_taken }}</dd>@endif
                @if ($ap->order_reference)<dt>{{ __('Order reference') }}</dt><dd>{{ $ap->order_reference }}</dd>@endif
            </dl>
            <h4 style="margin-top:.5rem">{{ __('Reasons recorded') }}</h4>
            <p class="muted" style="white-space:pre-wrap">{{ $ap->reasons }}</p>
            @if ($ap->conditions)
                <h4>{{ __('Conditions') }}</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $ap->conditions }}</p>
            @endif
            @if (! $loop->last)<hr class="divider">@endif
        @empty
            <p class="muted mb-0">{{ __('No decision has been recorded.') }}</p>
        @endforelse
    </div>
</div>

{{-- ============ 18. Tenancy agreement ============ --}}
@if ($a->agreement)
    <div class="card">
        <div class="card-head"><h3>{{ ++$sec }}. {{ __('Tenancy agreement') }}</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>{{ __('Agreement no.') }}</dt><dd>{{ $a->agreement->agreement_no }}</dd>
                <dt>{{ __('Executed on') }}</dt><dd>{{ $fmt($a->agreement->executed_on) }}</dd>
                <dt>{{ __('Monthly rent') }}</dt><dd>{{ $rs($a->agreement->monthly_rent) }}</dd>
                <dt>{{ __('Status') }}</dt><dd>{{ __(ucfirst(strtolower($a->agreement->status))) }}</dd>
            </dl>
        </div>
    </div>
@endif

{{-- ============ 19. Case history ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. {{ __('Complete case history') }}</h3></div>
    <div class="table-wrap" style="border:0;border-radius:0">
        <table class="data">
            <thead><tr><th>{{ __('When') }}</th><th>{{ __('From') }}</th><th>{{ __('To') }}</th><th>{{ __('By') }}</th><th>{{ __('Remarks') }}</th></tr></thead>
            <tbody>
            @foreach ($a->history as $h)
                <tr>
                    <td class="nowrap">{{ $fmt($h->occurred_at, 'd-m-Y H:i') }}</td>
                    <td class="faint">{{ __(\App\Services\WorkflowService::LABELS[$h->from_status] ?? ($h->from_status ?: '—')) }}</td>
                    <td>{{ __(\App\Services\WorkflowService::LABELS[$h->to_status] ?? ($h->to_status ?? '')) }}</td>
                    <td class="faint">{{ $h->actor_role ? __(ucwords(strtolower(str_replace('_', ' ', $h->actor_role)))) : '—' }}</td>
                    <td style="font-size:.82rem">{{ $h->remarks }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="faint mb-0" style="font-size:.8rem">
            {{ __('End of report. :count sections.', ['count' => $sec]) }}
            {{ __('Generated :date by :name.', ['date' => $generatedAt->translatedFormat('d F Y, H:i'), 'name' => __($generatedBy->name)]) }}
            {{ __('Scanned documents are held in the case file and are not reproduced here; each is listed in section 6 with its SHA-256 digest so the copy on record can be identified.') }}
        </p>
    </div>
</div>

@endsection
