@extends('layouts.app')

@section('title', 'Deep report')
@section('heading', 'Deep report — ' . $application->application_no)

@section('content')

@php
    $a = $application;
    $p = $a->property;
    $area = $p?->currentArea;
    $poss = $a->possession;
    $trace = $area ? (is_array($area->conversion_trace) ? $area->conversion_trace
                     : json_decode((string) $area->conversion_trace, true)) : null;
    $fmt = fn ($d, $f = 'd-m-Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
    $rs  = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
    $sec = 0;
@endphp

<div class="card no-print">
    <div class="card-body tight">
        <div class="btn-row mb-2">
            <a href="{{ route('applications.show', $a) }}" class="btn btn-ghost btn-sm">&larr; Case file</a>
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
        <h1 style="margin-bottom:.2rem">Evacuee Trust Property Board</h1>
        <p class="muted" style="margin-bottom:1.25rem">
            Regularization of Possession &mdash; Clause 3(ii), Scheme for the Management and
            Disposal of Urban Evacuee Trust Properties, 1977
        </p>

        <h2 style="margin-bottom:.35rem">{{ $a->application_no }}</h2>
        <p class="lede" style="margin-bottom:1rem">
            {{ $a->applicant?->nameWithParentage() }}<br>
            {{ $p?->identity() }}, {{ $p?->district?->name }}
        </p>

        <div class="inline-list" style="justify-content:center">
            <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">
                Payment {{ $a->payment_status }}
            </span>
            <span class="badge badge-{{ $a->statusTone() }}">{{ $a->statusLabel() }}</span>
            @if ($a->is_sub_judice)<span class="badge badge-danger">Sub judice</span>@endif
        </div>

        <p class="faint" style="font-size:.8rem;margin-top:1.25rem;margin-bottom:0">
            Complete case file, every element included.<br>
            Generated {{ $generatedAt->format('d F Y, H:i') }} by {{ $generatedBy->name }}
            ({{ $generatedBy->primaryRole()?->name }}).
        </p>
    </div>
</div>

{{-- ============ 1. Applicant ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Applicant particulars</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>Name</dt><dd>{{ $a->applicant?->full_name }}</dd>
            <dt>{{ $a->applicant?->parentage_type === 'HUSBAND' ? 'Husband' : 'Father' }}</dt>
            <dd>{{ $a->applicant?->parentage_name }}</dd>
            <dt>CNIC</dt><dd class="num">{{ $a->applicant?->formattedCnic() }}</dd>
            <dt>Contact</dt><dd>{{ $a->applicant?->contact }}</dd>
            @if ($a->applicant?->email)<dt>Email</dt><dd>{{ $a->applicant->email }}</dd>@endif
            <dt>Address</dt><dd>{{ $a->applicant?->postal_address }}</dd>
            @if ($a->applicant?->hasRemissionGround())
                <dt>Clause 12 ground</dt>
                <dd>
                    @if ($a->applicant->is_indigent)<span class="badge badge-gold">Indigent</span>@endif
                    @if ($a->applicant->is_widow)<span class="badge badge-gold">Widow</span>@endif
                    @if ($a->applicant->is_orphan)<span class="badge badge-gold">Orphan</span>@endif
                </dd>
            @endif
        </dl>
    </div>
</div>

{{-- ============ 2. Property ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Property particulars</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>Property no.</dt><dd>{{ $p?->property_no }}</dd>
            <dt>Sub-unit no.</dt><dd>{{ $p?->sub_unit_no ?: '—' }}</dd>
            <dt>Type</dt><dd>{{ $p?->typeLabel() }}</dd>
            <dt>Usage</dt><dd>{{ $p?->usageLabel() }}</dd>
            <dt>Address</dt><dd>{{ $p?->address }}</dd>
            <dt>Mouza</dt><dd>{{ $p?->mouza?->name ?: '—' }}</dd>
            <dt>City</dt><dd>{{ $p?->city ?: '—' }}</dd>
            <dt>Tehsil</dt><dd>{{ $p?->tehsil?->name ?: '—' }}</dd>
            <dt>District</dt><dd>{{ $p?->district?->name }}</dd>
            <dt>Province</dt><dd>{{ $p?->province?->name }}</dd>
            <dt>Khewat / Khatooni / Khasra</dt>
            <dd>{{ $p?->khewat_no ?: '—' }} / {{ $p?->khatooni_no ?: '—' }} / {{ $p?->khasra_no ?: '—' }}</dd>
        </dl>
    </div>
</div>

{{-- ============ 3. Area ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. Area, and how it was converted</h3>
        <div class="card-actions"><span class="clause">Pakistani revenue measure</span></div>
    </div>
    <div class="card-body">
        @if ($area)
            <dl class="kv">
                <dt>Area as entered</dt>
                <dd>
                    @if ($trace && ! empty($trace['components']))
                        {{ collect($trace['components'])
                            ->map(fn ($c) => rtrim(rtrim($c['quantity'], '0'), '.') . ' ' . $c['unit_name'])
                            ->implode(' + ') }}
                    @else
                        {{ $area->entered_value }} {{ $area->entered_unit_code }}
                    @endif
                </dd>
                <dt>Standard applied</dt><dd>{{ $a->unitProfile?->name }}</dd>
                <dt>Area in square feet</dt>
                <dd><strong>{{ number_format((float) $area->area_sqft, 4) }} sqft</strong></dd>
                @if ($area->covered_area_sqft)
                    <dt>Covered area</dt><dd>{{ number_format((float) $area->covered_area_sqft, 2) }} sqft</dd>
                @endif
            </dl>

            @if ($trace && ! empty($trace['components']))
                <h4 class="mt-2">Worked conversion</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                        <tr><th>Component</th><th class="num">Quantity</th><th class="num">sqft per unit</th><th class="num">Subtotal</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($trace['components'] as $c)
                            <tr>
                                <td>{{ $c['unit_name'] }}</td>
                                <td class="num">{{ rtrim(rtrim($c['quantity'], '0'), '.') }}</td>
                                <td class="num">{{ rtrim(rtrim($c['sqft_per_unit'], '0'), '.') }}</td>
                                <td class="num">{{ number_format((float) $c['subtotal_sqft'], 4) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr style="background:var(--tint);font-weight:650">
                            <td colspan="3">Total</td>
                            <td class="num">{{ number_format((float) $area->area_sqft, 4) }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="hint mt-1">
                    The factors above were frozen against this application when the area was
                    recorded, so a later change to the conversion table cannot restate it.
                </p>
            @endif
        @else
            <p class="muted mb-0">No area recorded.</p>
        @endif
    </div>
</div>

{{-- ============ 4. Location and geo-tag ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Location and geo-tagging</h3></div>
    <div class="card-body">
        <dl class="kv">
            <dt>Location</dt><dd>{{ $p?->locationChain() }}</dd>
        </dl>
        @if ($p?->geoTags->isNotEmpty())
            <div class="table-wrap mt-1">
                <table class="data">
                    <thead><tr><th class="num">Latitude</th><th class="num">Longitude</th><th>Source</th><th>Captured</th></tr></thead>
                    <tbody>
                    @foreach ($p->geoTags as $g)
                        <tr>
                            <td class="num">{{ $g->latitude }}</td>
                            <td class="num">{{ $g->longitude }}</td>
                            <td>{{ ucwords(strtolower(str_replace('_', ' ', $g->source))) }}</td>
                            <td class="nowrap">{{ $fmt($g->captured_at) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted mb-0">No geo coordinates recorded.</p>
        @endif
    </div>
</div>

{{-- ============ 5. Possession ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. Possession and eligibility</h3>
        <div class="card-actions"><span class="clause">Clause 3(ii)(a)–(b)</span></div>
    </div>
    <div class="card-body">
        @if ($poss)
            <dl class="kv">
                <dt>Date of possession</dt><dd><strong>{{ $fmt($poss->date_of_possession) }}</strong></dd>
                <dt>Nature</dt><dd>{{ ucfirst(strtolower($poss->possession_nature)) }}</dd>
                <dt>Cut-off applied</dt><dd>{{ $fmt($poss->cutoff_applied) }}</dd>
                <dt>Eligible</dt>
                <dd>
                    <span class="badge badge-{{ $poss->is_eligible ? 'good' : 'danger' }}">
                        {{ $poss->is_eligible ? 'Yes' : 'No' }}
                    </span>
                </dd>
                <dt>Judicial verdict</dt><dd>{{ $fmt($poss->date_of_judicial_verdict) }}</dd>
                <dt>Arrears run from</dt>
                <dd>
                    <strong>{{ $fmt($poss->arrears_from) }}</strong>
                    <span class="badge badge-neutral">
                        {{ ucwords(strtolower(str_replace('_', ' ', $poss->arrears_from_basis))) }}
                    </span>
                </dd>
            </dl>
            <div class="alert {{ $poss->is_eligible ? 'alert-good' : 'alert-danger' }} mt-2">
                @include('partials.icon', ['name' => $poss->is_eligible ? 'check' : 'alert'])
                <div><p class="mb-0">{{ $poss->eligibility_reason }}</p></div>
            </div>
            @if ($poss->possession_description)
                <h4>How possession was taken</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $poss->possession_description }}</p>
            @endif
        @else
            <p class="muted mb-0">No possession details recorded.</p>
        @endif
    </div>
</div>

{{-- ============ 6. Evidence ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. Schedule of evidence</h3>
        <span class="badge badge-neutral">{{ $a->documents->count() }}</span>
        <div class="card-actions"><span class="clause">Clause 3(ii)(c)</span></div>
    </div>
    @if ($a->documents->isEmpty())
        <div class="card-body"><p class="muted mb-0">No document filed.</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr>
                    <th>Head</th><th>Reference</th><th>Dated</th><th>Issuing authority</th>
                    <th>Certified</th><th>Status</th><th>Verified by</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($a->documents as $d)
                    <tr>
                        <td>{{ $d->documentType?->name }}
                            <div class="faint" style="font-size:.72rem;font-family:var(--font-mono)">
                                {{ substr($d->sha256, 0, 20) }}…
                            </div>
                        </td>
                        <td>{{ $d->reference_no ?: '—' }}</td>
                        <td class="nowrap">{{ $fmt($d->document_date) }}</td>
                        <td>{{ $d->issuing_authority ?: '—' }}</td>
                        <td>{{ $d->is_certified_copy ? 'Yes' : 'No' }}</td>
                        <td><span class="badge badge-{{ $d->status === 'VERIFIED' ? 'good' : ($d->status === 'WAIVED' ? 'gold' : 'neutral') }}">
                            {{ ucfirst(strtolower($d->status)) }}</span></td>
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
        <h3>{{ ++$sec }}. Rent assessment</h3>
        <div class="card-actions"><span class="clause">Clause 10</span></div>
    </div>
    <div class="card-body">
        @if ($round)
            <dl class="kv">
                <dt>Round</dt><dd>{{ $round->round_no }} ({{ ucfirst(strtolower($round->round_type)) }})</dd>
                <dt>Base date</dt><dd>{{ $fmt($round->base_date) }}</dd>
                <dt>Anchor date</dt><dd>{{ $fmt($round->effective_from) }}</dd>
                <dt>Enhancement</dt>
                <dd>{{ rtrim(rtrim($round->enhancement_rate, '0'), '.') }}% per annum,
                    {{ strtolower($round->enhancement_method) }} &mdash;
                    <span class="clause">Clause 11(ii)</span></dd>
                <dt>Re-assessment cycle</dt><dd>{{ $round->reassessment_cycle_years }} years &mdash;
                    <span class="clause">Clause 11(i)</span></dd>
                <dt>Rent proposed</dt><dd>{{ $round->proposed_monthly_rent ? $rs($round->proposed_monthly_rent) : '—' }}</dd>
                <dt>Rent determined</dt>
                <dd><strong>{{ $round->determined_monthly_rent ? $rs($round->determined_monthly_rent) : '—' }}</strong></dd>
            </dl>

            @if ($round->rateInputs->isNotEmpty())
                <h4 class="mt-2">Evidence of value relied on</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Source</th><th class="num">Rate</th><th>Basis</th><th>Reference</th></tr></thead>
                        <tbody>
                        @foreach ($round->rateInputs as $in)
                            <tr>
                                <td>{{ $in->rateSource?->name }}</td>
                                <td class="num">{{ $rs($in->rate_value) }}</td>
                                <td class="faint">{{ strtolower(str_replace('_', ' ', $in->rate_unit)) }}</td>
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
                <h4 class="mt-2">Prevailing market rent of adjoining properties</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Property</th><th class="num">Area</th><th class="num">Rent</th><th class="num">Rs./sqft</th><th>Source</th></tr></thead>
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
                            Rent fixed at {{ $rs($dec->determined_monthly_rent) }} per month
                            @if ($dec->is_superseded) (superseded) @endif
                        </strong>
                        <div class="faint" style="font-size:.8rem">
                            Decided {{ $fmt($dec->decided_at, 'd-m-Y H:i') }}
                        </div>
                        <p style="white-space:pre-wrap;margin:.5rem 0 0">{{ $dec->reasons }}</p>
                        @if ($dec->objections_considered)
                            <p style="white-space:pre-wrap;margin:.5rem 0 0"><em>{{ $dec->objections_considered }}</em></p>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <p class="muted mb-0">No assessment has been opened.</p>
        @endif
    </div>
</div>

{{-- ============ 8. Rent table ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. Rent, in the milestone years</h3>
        <div class="card-actions"><span class="clause">2000 · 2004 · 2008 · 2012 · 2016 · 2020 · 2024</span></div>
    </div>
    <div class="table-wrap" style="border:0;border-radius:0">
        <table class="data">
            <thead><tr><th>Rent year</th><th>Period</th><th class="num">Monthly rent</th><th class="num">Annual rent</th></tr></thead>
            <tbody>
            @foreach ($milestones as $m)
                <tr class="{{ $m['in_scope'] ? 'is-milestone' : 'row-muted' }}">
                    <td><strong>{{ $m['year'] }}</strong></td>
                    <td class="nowrap">{{ $m['period'] }}</td>
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
        <h3>{{ ++$sec }}. Objectors, their pleas, and the decisions</h3>
        <span class="badge badge-neutral">{{ $a->objections->count() }}</span>
    </div>
    <div class="card-body">
        @forelse ($a->objections as $o)
            @php $d = $objectionDecisions->get($o->id); @endphp
            <div class="inline-list">
                <strong>{{ $o->objector_name }}</strong>
                @if ($o->objector_parentage)<span class="faint">s/o {{ $o->objector_parentage }}</span>@endif
                <span class="badge badge-{{ $o->status === 'DECIDED' ? 'good' : 'warn' }}">{{ ucfirst(strtolower($o->status)) }}</span>
                @unless ($o->is_within_time)<span class="badge badge-danger">Out of time</span>@endunless
            </div>
            <dl class="kv mt-1" style="font-size:.84rem">
                <dt>CNIC</dt><dd class="num">{{ $o->objector_cnic ?: '—' }}</dd>
                <dt>Contact</dt><dd>{{ $o->objector_contact ?: '—' }}</dd>
                <dt>Relationship</dt><dd>{{ $o->relationship_to_property ?: '—' }}</dd>
                <dt>Filed on</dt><dd>{{ $fmt($o->filed_on) }}</dd>
            </dl>
            <h4 style="margin-top:.6rem">Plea</h4>
            <p class="muted" style="white-space:pre-wrap">{{ $o->plea }}</p>
            @if ($d)
                <h4>Decision &mdash; {{ ucwords(strtolower(str_replace('_', ' ', $d->decision))) }}</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $d->reasons }}</p>
            @endif
            @if (! $loop->last)<hr class="divider">@endif
        @empty
            <p class="muted mb-0">No objection was filed.</p>
        @endforelse
    </div>
</div>

{{-- ============ 10. Notices and hearings ============ ---}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Notices and hearings</h3></div>
    <div class="card-body">
        @if ($a->notices->isNotEmpty())
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Notice</th><th>Type</th><th>Issued</th><th>Served</th><th>Mode</th><th>Objections until</th></tr></thead>
                    <tbody>
                    @foreach ($a->notices as $n)
                        <tr>
                            <td style="font-size:.8rem">{{ $n->notice_no }}</td>
                            <td>{{ ucfirst(strtolower($n->notice_type)) }}</td>
                            <td class="nowrap">{{ $fmt($n->issued_on) }}</td>
                            <td class="nowrap">{{ $fmt($n->served_on) }}</td>
                            <td class="faint">{{ ucwords(strtolower(str_replace('_', ' ', $n->service_mode))) }}</td>
                            <td class="nowrap">{{ $fmt($n->objection_deadline) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="muted">No notice issued.</p>
        @endif

        @foreach ($a->hearings as $h)
            <h4 class="mt-2">Hearing {{ $fmt($h->scheduled_for, 'd-m-Y H:i') }} &mdash; {{ ucfirst(strtolower($h->status)) }}</h4>
            @if ($h->venue)<p class="faint" style="font-size:.82rem;margin-bottom:.3rem">{{ $h->venue }}</p>@endif
            @if ($h->proceedings)
                <p class="muted" style="white-space:pre-wrap">{{ $h->proceedings }}</p>
            @endif
        @endforeach
    </div>
</div>

{{-- ============ 11. Occupant offers ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Rent offered by other occupants</h3></div>
    @if ($a->occupantOffers->isEmpty())
        <div class="card-body"><p class="muted mb-0">No competing offer recorded.</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>Occupant</th><th>CNIC</th><th>Portion</th><th class="num">Rent offered</th><th>Offered on</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($a->occupantOffers as $o)
                    <tr>
                        <td>{{ $o->occupant_name }}</td>
                        <td class="num">{{ $o->occupant_cnic ?: '—' }}</td>
                        <td>{{ $o->portion_occupied ?: '—' }}</td>
                        <td class="num">{{ $rs($o->rent_offered) }}</td>
                        <td class="nowrap">{{ $fmt($o->offer_date) }}</td>
                        <td>{{ ucwords(strtolower(str_replace('_', ' ', $o->status))) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============ 12. Litigation ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Litigation</h3></div>
    @if ($a->litigations->isEmpty())
        <div class="card-body"><p class="muted mb-0">No case on record.</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>Court</th><th>Case no.</th><th>Type</th><th>Pending</th><th>Restraining order</th><th>Direction case</th><th>Outcome</th></tr></thead>
                <tbody>
                @foreach ($a->litigations as $l)
                    <tr>
                        <td>{{ $l->court_name }}</td>
                        <td>{{ $l->case_no }}</td>
                        <td>{{ ucwords(strtolower(str_replace('_', ' ', $l->case_type))) }}</td>
                        <td>{{ $l->is_pending ? 'Yes' : 'No' }}</td>
                        <td>{{ $l->has_restraining_order ? 'Yes — ' . $fmt($l->restraining_order_date) : 'No' }}</td>
                        <td>{{ $l->is_direction_case ? 'Yes' : 'No' }}</td>
                        <td>{{ ucfirst(strtolower($l->outcome)) }}</td>
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
        <h3>{{ ++$sec }}. Processing fee</h3>
        <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">{{ $a->payment_status }}</span>
    </div>
    @if ($a->feePayments->isEmpty())
        <div class="card-body"><p class="muted mb-0">No deposit recorded.</p></div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>Instrument</th><th>No.</th><th>Dated</th><th class="num">Amount</th><th>Bank / branch</th><th>Depositor</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($a->feePayments as $f)
                    <tr>
                        <td>{{ ucwords(strtolower(str_replace('_', ' ', $f->instrument_type))) }}</td>
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
                        <td><span class="badge badge-{{ $f->status === 'VERIFIED' ? 'good' : 'warn' }}">{{ ucfirst(strtolower($f->status)) }}</span></td>
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
        <h3>{{ ++$sec }}. Arrears ledger</h3>
        <div class="card-actions"><span class="clause">Clause 3(ii)(b)</span></div>
    </div>
    <div class="card-body">
        <dl class="kv">
            <dt>Assessed</dt><dd>{{ $rs($arrears['total_due']) }}</dd>
            <dt>Recovered</dt><dd>{{ $rs($arrears['total_paid']) }}</dd>
            <dt>Remitted</dt><dd>{{ $rs($arrears['total_remitted']) }}</dd>
            <dt>Balance</dt><dd><strong>{{ $rs($arrears['balance']) }}</strong></dd>
        </dl>
        <div class="alert {{ $clearance['satisfied'] ? 'alert-good' : 'alert-warn' }} mt-1">
            @include('partials.icon', ['name' => $clearance['satisfied'] ? 'check' : 'alert'])
            <div><p class="mb-0">{{ $clearance['reason'] }}</p></div>
        </div>
    </div>
    @if ($ledger->isNotEmpty())
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead><tr><th>Year</th><th>Period</th><th class="num">Monthly</th><th class="num">Months</th><th class="num">Due</th><th class="num">Paid</th><th class="num">Remitted</th><th class="num">Balance</th></tr></thead>
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
        <div class="card-head"><h3>{{ ++$sec }}. Recovery</h3></div>
        <div class="card-body">
            @if ($receipts->isNotEmpty())
                <h4>Receipts</h4>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Mode</th><th class="num">Amount</th></tr></thead>
                        <tbody>
                        @foreach ($receipts as $r)
                            <tr>
                                <td>{{ $r->receipt_no }}</td>
                                <td class="nowrap">{{ $fmt($r->receipt_date) }}</td>
                                <td>{{ ucwords(strtolower(str_replace('_', ' ', $r->payment_mode))) }}</td>
                                <td class="num">{{ $rs($r->amount) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @foreach ($instalmentPlans as $pl)
                <h4 class="mt-2">Instalment plan &mdash; <span class="clause">Clause 13</span></h4>
                <dl class="kv">
                    <dt>Total</dt><dd>{{ $rs($pl->total_amount) }}</dd>
                    <dt>Instalments</dt><dd>{{ $pl->instalment_count }} &times; {{ $rs($pl->instalment_amount) }}</dd>
                    <dt>Status</dt><dd>{{ ucfirst(strtolower($pl->status)) }}</dd>
                </dl>
                @if ($pl->justification)<p class="muted" style="white-space:pre-wrap">{{ $pl->justification }}</p>@endif
            @endforeach

            @foreach ($remissions as $rm)
                <h4 class="mt-2">Remission &mdash; <span class="clause">Clause 12</span></h4>
                <dl class="kv">
                    <dt>Ground</dt><dd>{{ ucfirst(strtolower($rm->ground)) }}</dd>
                    <dt>Type</dt><dd>{{ ucwords(strtolower(str_replace('_', ' ', $rm->remission_type))) }}</dd>
                    <dt>Status</dt><dd>{{ ucfirst(strtolower($rm->status)) }}</dd>
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
        <h3>{{ ++$sec }}. Nominee and legal heirs</h3>
        <div class="card-actions"><span class="clause">Scheme para 3(iii)(B)</span></div>
    </div>
    <div class="card-body">
        @forelse ($a->nominees as $n)
            <dl class="kv">
                <dt>Nominee</dt><dd>{{ $n->nominee_name }}</dd>
                <dt>Relationship</dt><dd>{{ $n->relationship }}</dd>
                <dt>CNIC</dt><dd class="num">{{ $n->nominee_cnic ?: '—' }}</dd>
                <dt>Form received</dt><dd>{{ $fmt($n->form_received_on) }}</dd>
            </dl>
            @if ($n->heirs->isNotEmpty())
                <h4>Legal heirs</h4>
                <ol style="margin:0;padding-inline-start:1.2rem">
                    @foreach ($n->heirs as $h)
                        <li>{{ $h->heir_name }} &mdash; {{ $h->relationship }}</li>
                    @endforeach
                </ol>
            @endif
        @empty
            <p class="muted mb-0">
                No nomination form on record. Under the proviso to Scheme para 3(iii)(B) the
                District Officer shall not regularize the possession until it is obtained.
            </p>
        @endforelse
    </div>
</div>

{{-- ============ 17. Approvals ============ --}}
<div class="card">
    <div class="card-head">
        <h3>{{ ++$sec }}. Decisions and approvals</h3>
        <div class="card-actions"><span class="clause">Clause 3(ii)(d)</span></div>
    </div>
    <div class="card-body">
        @forelse ($a->approvals as $ap)
            <div class="inline-list">
                <strong>{{ ucwords(strtolower(str_replace('_', ' ', $ap->level))) }}</strong>
                <span class="badge badge-{{ $ap->action === 'APPROVE' ? 'good' : ($ap->action === 'REJECT' ? 'danger' : 'warn') }}">
                    {{ ucfirst(strtolower($ap->action)) }}
                </span>
                @if (! $ap->is_within_sla)<span class="badge badge-danger">Beyond the statutory limit</span>@endif
            </div>
            <dl class="kv mt-1" style="font-size:.84rem">
                <dt>Acted on</dt><dd>{{ $fmt($ap->acted_at, 'd-m-Y H:i') }}</dd>
                <dt>Due by</dt><dd>{{ $fmt($ap->due_by) }}</dd>
                @if ($ap->days_taken !== null)<dt>Days taken</dt><dd>{{ $ap->days_taken }}</dd>@endif
                @if ($ap->order_reference)<dt>Order reference</dt><dd>{{ $ap->order_reference }}</dd>@endif
            </dl>
            <h4 style="margin-top:.5rem">Reasons recorded</h4>
            <p class="muted" style="white-space:pre-wrap">{{ $ap->reasons }}</p>
            @if ($ap->conditions)
                <h4>Conditions</h4>
                <p class="muted" style="white-space:pre-wrap">{{ $ap->conditions }}</p>
            @endif
            @if (! $loop->last)<hr class="divider">@endif
        @empty
            <p class="muted mb-0">No decision has been recorded.</p>
        @endforelse
    </div>
</div>

{{-- ============ 18. Tenancy agreement ============ --}}
@if ($a->agreement)
    <div class="card">
        <div class="card-head"><h3>{{ ++$sec }}. Tenancy agreement</h3></div>
        <div class="card-body">
            <dl class="kv">
                <dt>Agreement no.</dt><dd>{{ $a->agreement->agreement_no }}</dd>
                <dt>Executed on</dt><dd>{{ $fmt($a->agreement->executed_on) }}</dd>
                <dt>Monthly rent</dt><dd>{{ $rs($a->agreement->monthly_rent) }}</dd>
                <dt>Status</dt><dd>{{ ucfirst(strtolower($a->agreement->status)) }}</dd>
            </dl>
        </div>
    </div>
@endif

{{-- ============ 19. Case history ============ --}}
<div class="card">
    <div class="card-head"><h3>{{ ++$sec }}. Complete case history</h3></div>
    <div class="table-wrap" style="border:0;border-radius:0">
        <table class="data">
            <thead><tr><th>When</th><th>From</th><th>To</th><th>By</th><th>Remarks</th></tr></thead>
            <tbody>
            @foreach ($a->history as $h)
                <tr>
                    <td class="nowrap">{{ $fmt($h->occurred_at, 'd-m-Y H:i') }}</td>
                    <td class="faint">{{ \App\Services\WorkflowService::LABELS[$h->from_status] ?? ($h->from_status ?: '—') }}</td>
                    <td>{{ \App\Services\WorkflowService::LABELS[$h->to_status] ?? $h->to_status }}</td>
                    <td class="faint">{{ $h->actor_role ? ucwords(strtolower(str_replace('_', ' ', $h->actor_role))) : '—' }}</td>
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
            End of report. {{ $sec }} sections.
            Generated {{ $generatedAt->format('d F Y, H:i') }} by {{ $generatedBy->name }}.
            Scanned documents are held in the case file and are not reproduced here; each is
            listed in section 6 with its SHA-256 digest so the copy on record can be identified.
        </p>
    </div>
</div>

@endsection
