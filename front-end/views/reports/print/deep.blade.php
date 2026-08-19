@extends('layouts.print')

@section('doc-title', 'Deep report — ' . $application->application_no)
@section('doc-subject', 'Complete case file — ' . $application->application_no)

@section('doc-body')

@php
    $a = $application;
    $p = $a->property;
    $area = $p?->currentArea;
    $poss = $a->possession;
    $trace = $area ? (is_array($area->conversion_trace) ? $area->conversion_trace
                     : json_decode((string) $area->conversion_trace, true)) : null;
    $fmt = fn ($d, $f = 'd-m-Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
    $rs  = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
    $n   = 0;
@endphp

<h1>{{ $a->application_no }}</h1>
<p class="muted">
    {{ $a->applicant?->nameWithParentage() }} &mdash; {{ $p?->identity() }}, {{ $p?->district?->name }}<br>
    Payment <strong>{{ $a->payment_status }}</strong> &middot;
    Stage <strong>{{ $a->statusLabel() }}</strong>
    @if ($a->is_sub_judice) &middot; <strong>Sub judice</strong> @endif
</p>

<div class="note">
    Complete case file, every element included, in the order set out in the requirements.
</div>

<h2>{{ ++$n }}. Applicant particulars</h2>
<table class="kv">
    <tr><td class="k">Name</td><td class="v">{{ $a->applicant?->full_name }}</td></tr>
    <tr><td class="k">{{ $a->applicant?->parentage_type === 'HUSBAND' ? 'Husband' : 'Father' }}</td>
        <td class="v">{{ $a->applicant?->parentage_name }}</td></tr>
    <tr><td class="k">CNIC</td><td class="v">{{ $a->applicant?->formattedCnic() }}</td></tr>
    <tr><td class="k">Contact</td><td class="v">{{ $a->applicant?->contact }}</td></tr>
    <tr><td class="k">Address</td><td class="v">{{ $a->applicant?->postal_address }}</td></tr>
    @if ($a->applicant?->hasRemissionGround())
        <tr><td class="k">Clause 12 ground</td><td class="v">
            @if ($a->applicant->is_indigent) Indigent @endif
            @if ($a->applicant->is_widow) Widow @endif
            @if ($a->applicant->is_orphan) Orphan @endif
        </td></tr>
    @endif
</table>

<h2>{{ ++$n }}. Property particulars</h2>
<table class="kv">
    <tr><td class="k">Property no.</td><td class="v">{{ $p?->property_no }}</td></tr>
    <tr><td class="k">Sub-unit no.</td><td class="v">{{ $p?->sub_unit_no ?: '—' }}</td></tr>
    <tr><td class="k">Type / usage</td><td class="v">{{ $p?->typeLabel() }} &mdash; {{ $p?->usageLabel() }}</td></tr>
    <tr><td class="k">Address</td><td class="v">{{ $p?->address }}</td></tr>
    <tr><td class="k">Mouza</td><td class="v">{{ $p?->mouza?->name ?: '—' }}</td></tr>
    <tr><td class="k">City</td><td class="v">{{ $p?->city ?: '—' }}</td></tr>
    <tr><td class="k">Tehsil</td><td class="v">{{ $p?->tehsil?->name ?: '—' }}</td></tr>
    <tr><td class="k">District</td><td class="v">{{ $p?->district?->name }}</td></tr>
    <tr><td class="k">Province</td><td class="v">{{ $p?->province?->name }}</td></tr>
    <tr><td class="k">Khewat / Khatooni / Khasra</td>
        <td class="v">{{ $p?->khewat_no ?: '—' }} / {{ $p?->khatooni_no ?: '—' }} / {{ $p?->khasra_no ?: '—' }}</td></tr>
</table>

<h2>{{ ++$n }}. Area and its conversion</h2>
@if ($area)
    <table class="kv">
        <tr><td class="k">Standard applied</td><td class="v">{{ $a->unitProfile?->name }}</td></tr>
        <tr><td class="k">Area in square feet</td>
            <td class="v">{{ number_format((float) $area->area_sqft, 4) }} sqft</td></tr>
        @if ($area->covered_area_sqft)
            <tr><td class="k">Covered area</td>
                <td class="v">{{ number_format((float) $area->covered_area_sqft, 2) }} sqft</td></tr>
        @endif
    </table>

    @if ($trace && ! empty($trace['components']))
        <table class="t">
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
            <tr><td colspan="3">Total</td>
                <td class="num">{{ number_format((float) $area->area_sqft, 4) }}</td></tr>
            </tfoot>
        </table>
        <p class="faint">
            These factors were frozen against this application when the area was recorded, so a
            later change to the conversion table cannot restate it.
        </p>
    @endif
@else
    <p class="muted">No area recorded.</p>
@endif

<h2>{{ ++$n }}. Location and geo-tagging</h2>
<p>{{ $p?->locationChain() }}</p>
@if ($p?->geoTags->isNotEmpty())
    <table class="t">
        <thead><tr><th class="num">Latitude</th><th class="num">Longitude</th><th>Source</th><th>Captured</th></tr></thead>
        <tbody>
        @foreach ($p->geoTags as $g)
            <tr>
                <td class="num">{{ $g->latitude }}</td>
                <td class="num">{{ $g->longitude }}</td>
                <td>{{ ucwords(strtolower(str_replace('_', ' ', $g->source))) }}</td>
                <td>{{ $fmt($g->captured_at) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="muted">No geo coordinates recorded.</p>
@endif

<h2>{{ ++$n }}. Possession and eligibility</h2>
@if ($poss)
    <table class="kv">
        <tr><td class="k">Date of possession</td><td class="v">{{ $fmt($poss->date_of_possession) }}</td></tr>
        <tr><td class="k">Nature</td><td class="v">{{ ucfirst(strtolower($poss->possession_nature)) }}</td></tr>
        <tr><td class="k">Cut-off applied</td><td class="v">{{ $fmt($poss->cutoff_applied) }}</td></tr>
        <tr><td class="k">Eligible</td><td class="v">{{ $poss->is_eligible ? 'Yes' : 'No' }}</td></tr>
        <tr><td class="k">Judicial verdict</td><td class="v">{{ $fmt($poss->date_of_judicial_verdict) }}</td></tr>
        <tr><td class="k">Arrears run from</td>
            <td class="v">{{ $fmt($poss->arrears_from) }}
                ({{ ucwords(strtolower(str_replace('_', ' ', $poss->arrears_from_basis))) }})</td></tr>
    </table>
    <div class="{{ $poss->is_eligible ? 'note' : 'danger' }}">{{ $poss->eligibility_reason }}</div>
    @if ($poss->possession_description)
        <h3>How possession was taken</h3>
        <div class="quote">{{ $poss->possession_description }}</div>
    @endif
@endif

<h2>{{ ++$n }}. Schedule of evidence</h2>
@if ($a->documents->isEmpty())
    <p class="muted">No document filed.</p>
@else
    <table class="t">
        <thead>
        <tr><th>Head</th><th>Reference</th><th>Dated</th><th>Issuing authority</th>
            <th>Certified</th><th>Status</th><th>Verified</th></tr>
        </thead>
        <tbody>
        @foreach ($a->documents as $d)
            <tr>
                <td>{{ $d->documentType?->name }}<br>
                    <span class="faint">{{ substr($d->sha256, 0, 20) }}…</span></td>
                <td>{{ $d->reference_no ?: '—' }}</td>
                <td>{{ $fmt($d->document_date) }}</td>
                <td>{{ $d->issuing_authority ?: '—' }}</td>
                <td>{{ $d->is_certified_copy ? 'Yes' : 'No' }}</td>
                <td>{{ ucfirst(strtolower($d->status)) }}</td>
                <td>{{ $fmt($d->verified_at) }}
                    @if ($d->verification_remarks)<br><span class="faint">{{ $d->verification_remarks }}</span>@endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ ++$n }}. Rent assessment</h2>
@if ($round)
    <table class="kv">
        <tr><td class="k">Round</td><td class="v">{{ $round->round_no }} ({{ ucfirst(strtolower($round->round_type)) }})</td></tr>
        <tr><td class="k">Base date</td><td class="v">{{ $fmt($round->base_date) }}</td></tr>
        <tr><td class="k">Anchor date</td><td class="v">{{ $fmt($round->effective_from) }}</td></tr>
        <tr><td class="k">Enhancement</td>
            <td class="v">{{ rtrim(rtrim($round->enhancement_rate, '0'), '.') }}% per annum,
                {{ strtolower($round->enhancement_method) }} — Clause 11(ii)</td></tr>
        <tr><td class="k">Re-assessment cycle</td>
            <td class="v">{{ $round->reassessment_cycle_years }} years — Clause 11(i)</td></tr>
        <tr><td class="k">Rent proposed</td>
            <td class="v">{{ $round->proposed_monthly_rent ? $rs($round->proposed_monthly_rent) : '—' }}</td></tr>
        <tr><td class="k">Rent determined</td>
            <td class="v">{{ $round->determined_monthly_rent ? $rs($round->determined_monthly_rent) : '—' }}</td></tr>
    </table>

    @if ($round->rateInputs->isNotEmpty())
        <h3>Evidence of value relied on</h3>
        <table class="t">
            <thead><tr><th>Source</th><th class="num">Rate</th><th>Basis</th><th>Reference</th></tr></thead>
            <tbody>
            @foreach ($round->rateInputs as $in)
                <tr>
                    <td>{{ $in->rateSource?->name }}</td>
                    <td class="num">{{ $rs($in->rate_value) }}</td>
                    <td>{{ strtolower(str_replace('_', ' ', $in->rate_unit)) }}</td>
                    <td>{{ $in->notification_no ?: $in->report_no ?: '—' }}
                        @if ($in->notification_date) &middot; {{ $fmt($in->notification_date) }} @endif
                        @if ($in->valuator_name) &middot; {{ $in->valuator_name }} @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if ($round->comparables->isNotEmpty())
        <h3>Prevailing market rent of adjoining properties</h3>
        <table class="t">
            <thead><tr><th>Property</th><th class="num">Area</th><th class="num">Rent</th>
                       <th class="num">Rs./sqft</th><th>Source</th></tr></thead>
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
                    <td>{{ $c->information_source ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @foreach ($decisions as $dec)
        <h3>Determination {{ $dec->is_superseded ? '(superseded)' : '' }}
            &mdash; {{ $rs($dec->determined_monthly_rent) }} per month</h3>
        <p class="faint">Decided {{ $fmt($dec->decided_at, 'd-m-Y H:i') }}</p>
        <div class="quote">{{ $dec->reasons }}</div>
        @if ($dec->objections_considered)
            <div class="quote">{{ $dec->objections_considered }}</div>
        @endif
    @endforeach
@else
    <p class="muted">No assessment has been opened.</p>
@endif

<h2>{{ ++$n }}. Rent in the milestone years</h2>
<table class="t">
    <thead><tr><th>Rent year</th><th>Period</th><th class="num">Monthly rent</th><th class="num">Annual rent</th></tr></thead>
    <tbody>
    @foreach ($milestones as $m)
        <tr @if ($m['in_scope']) class="hi" @endif>
            <td>{{ $m['year'] }}</td>
            <td>{{ $m['period'] }}</td>
            <td class="num">{{ $m['monthly_rent'] ? $rs($m['monthly_rent']) : '—' }}</td>
            <td class="num">{{ $m['annual_rent'] ? $rs($m['annual_rent']) : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>{{ ++$n }}. Objectors, pleas and decisions</h2>
@forelse ($a->objections as $o)
    @php $d = $objectionDecisions->get($o->id); @endphp
    <h3>{{ $o->objector_name }}
        @if ($o->objector_parentage) s/o {{ $o->objector_parentage }} @endif</h3>
    <table class="kv">
        <tr><td class="k">Objection no.</td><td class="v">{{ $o->objection_no }}</td></tr>
        <tr><td class="k">CNIC</td><td class="v">{{ $o->objector_cnic ?: '—' }}</td></tr>
        <tr><td class="k">Relationship</td><td class="v">{{ $o->relationship_to_property ?: '—' }}</td></tr>
        <tr><td class="k">Filed on</td>
            <td class="v">{{ $fmt($o->filed_on) }} ({{ $o->is_within_time ? 'in time' : 'out of time' }})</td></tr>
    </table>
    <p><strong>Plea</strong></p>
    <div class="quote">{{ $o->plea }}</div>
    @if ($d)
        <p><strong>Decision &mdash; {{ ucwords(strtolower(str_replace('_', ' ', $d->decision))) }}</strong></p>
        <div class="quote">{{ $d->reasons }}</div>
    @endif
@empty
    <p class="muted">No objection was filed.</p>
@endforelse

<h2>{{ ++$n }}. Notices and hearings</h2>
@if ($a->notices->isNotEmpty())
    <table class="t">
        <thead><tr><th>Notice</th><th>Type</th><th>Issued</th><th>Served</th><th>Mode</th><th>Objections until</th></tr></thead>
        <tbody>
        @foreach ($a->notices as $nt)
            <tr>
                <td>{{ $nt->notice_no }}</td>
                <td>{{ ucfirst(strtolower($nt->notice_type)) }}</td>
                <td>{{ $fmt($nt->issued_on) }}</td>
                <td>{{ $fmt($nt->served_on) }}</td>
                <td>{{ ucwords(strtolower(str_replace('_', ' ', $nt->service_mode))) }}</td>
                <td>{{ $fmt($nt->objection_deadline) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p class="muted">No notice issued.</p>
@endif

@foreach ($a->hearings as $hr)
    <h3>Hearing {{ $fmt($hr->scheduled_for, 'd-m-Y H:i') }} &mdash; {{ ucfirst(strtolower($hr->status)) }}</h3>
    @if ($hr->venue)<p class="faint">{{ $hr->venue }}</p>@endif
    @if ($hr->proceedings)<div class="quote">{{ $hr->proceedings }}</div>@endif
@endforeach

<h2>{{ ++$n }}. Rent offered by other occupants</h2>
@if ($a->occupantOffers->isEmpty())
    <p class="muted">No competing offer recorded.</p>
@else
    <table class="t">
        <thead><tr><th>Occupant</th><th>CNIC</th><th>Portion</th><th class="num">Rent offered</th>
                   <th>Offered on</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($a->occupantOffers as $o)
            <tr>
                <td>{{ $o->occupant_name }}</td>
                <td>{{ $o->occupant_cnic ?: '—' }}</td>
                <td>{{ $o->portion_occupied ?: '—' }}</td>
                <td class="num">{{ $rs($o->rent_offered) }}</td>
                <td>{{ $fmt($o->offer_date) }}</td>
                <td>{{ ucwords(strtolower(str_replace('_', ' ', $o->status))) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ ++$n }}. Litigation</h2>
@if ($a->litigations->isEmpty())
    <p class="muted">No case on record.</p>
@else
    <table class="t">
        <thead><tr><th>Court</th><th>Case no.</th><th>Type</th><th>Pending</th>
                   <th>Restraining order</th><th>Direction case</th><th>Outcome</th></tr></thead>
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
@endif

<h2>{{ ++$n }}. Processing fee</h2>
<p>Payment status: <strong>{{ $a->payment_status }}</strong></p>
@if ($a->feePayments->isEmpty())
    <p class="muted">No deposit recorded.</p>
@else
    <table class="t">
        <thead><tr><th>Instrument</th><th>No.</th><th>Dated</th><th class="num">Amount</th>
                   <th>Bank / branch</th><th>Depositor</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($a->feePayments as $f)
            <tr>
                <td>{{ ucwords(strtolower(str_replace('_', ' ', $f->instrument_type))) }}</td>
                <td>{{ $f->instrument_no }}</td>
                <td>{{ $fmt($f->instrument_date) }}</td>
                <td class="num">{{ $rs($f->amount) }}</td>
                <td>{{ $f->bank_name }}<br><span class="faint">{{ $f->branch_name }}
                    @if ($f->branch_code) &middot; {{ $f->branch_code }} @endif</span></td>
                <td>{{ $f->depositor_name }}<br><span class="faint">{{ $f->depositor_cnic }}</span></td>
                <td>{{ ucfirst(strtolower($f->status)) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>{{ ++$n }}. Arrears ledger</h2>
<table class="kv">
    <tr><td class="k">Assessed</td><td class="v">{{ $rs($arrears['total_due']) }}</td></tr>
    <tr><td class="k">Recovered</td><td class="v">{{ $rs($arrears['total_paid']) }}</td></tr>
    <tr><td class="k">Remitted</td><td class="v">{{ $rs($arrears['total_remitted']) }}</td></tr>
    <tr><td class="k">Balance</td><td class="v">{{ $rs($arrears['balance']) }}</td></tr>
</table>
<div class="{{ $clearance['satisfied'] ? 'note' : 'warn' }}">{{ $clearance['reason'] }}</div>

@if ($ledger->isNotEmpty())
    <table class="t">
        <thead><tr><th>Year</th><th>Period</th><th class="num">Monthly</th><th class="num">Months</th>
                   <th class="num">Due</th><th class="num">Paid</th><th class="num">Remitted</th>
                   <th class="num">Balance</th></tr></thead>
        <tbody>
        @foreach ($ledger as $r)
            <tr>
                <td>{{ $r->period_year }}</td>
                <td>{{ $fmt($r->period_from) }} – {{ $fmt($r->period_to) }}</td>
                <td class="num">{{ number_format((float) $r->monthly_rent, 2) }}</td>
                <td class="num">{{ rtrim(rtrim($r->months_applicable, '0'), '.') }}</td>
                <td class="num">{{ number_format((float) $r->amount_due, 2) }}</td>
                <td class="num">{{ number_format((float) $r->amount_paid, 2) }}</td>
                <td class="num">{{ number_format((float) $r->remission_amount, 2) }}</td>
                <td class="num">{{ number_format((float) $r->balance, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4">Total</td>
            <td class="num">{{ number_format((float) $arrears['total_due'], 2) }}</td>
            <td class="num">{{ number_format((float) $arrears['total_paid'], 2) }}</td>
            <td class="num">{{ number_format((float) $arrears['total_remitted'], 2) }}</td>
            <td class="num">{{ number_format((float) $arrears['balance'], 2) }}</td>
        </tr>
        </tfoot>
    </table>
@endif

@if ($receipts->isNotEmpty() || count($instalmentPlans) || count($remissions))
    <h2>{{ ++$n }}. Recovery</h2>

    @if ($receipts->isNotEmpty())
        <h3>Receipts</h3>
        <table class="t">
            <thead><tr><th>Receipt</th><th>Date</th><th>Mode</th><th class="num">Amount</th></tr></thead>
            <tbody>
            @foreach ($receipts as $r)
                <tr>
                    <td>{{ $r->receipt_no }}</td>
                    <td>{{ $fmt($r->receipt_date) }}</td>
                    <td>{{ ucwords(strtolower(str_replace('_', ' ', $r->payment_mode))) }}</td>
                    <td class="num">{{ $rs($r->amount) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @foreach ($instalmentPlans as $pl)
        <h3>Instalment plan &mdash; Clause 13</h3>
        <table class="kv">
            <tr><td class="k">Total</td><td class="v">{{ $rs($pl->total_amount) }}</td></tr>
            <tr><td class="k">Instalments</td>
                <td class="v">{{ $pl->instalment_count }} &times; {{ $rs($pl->instalment_amount) }}</td></tr>
            <tr><td class="k">Status</td><td class="v">{{ ucfirst(strtolower($pl->status)) }}</td></tr>
        </table>
        @if ($pl->justification)<div class="quote">{{ $pl->justification }}</div>@endif
    @endforeach

    @foreach ($remissions as $rm)
        <h3>Remission &mdash; Clause 12</h3>
        <table class="kv">
            <tr><td class="k">Ground</td><td class="v">{{ ucfirst(strtolower($rm->ground)) }}</td></tr>
            <tr><td class="k">Type</td>
                <td class="v">{{ ucwords(strtolower(str_replace('_', ' ', $rm->remission_type))) }}</td></tr>
            <tr><td class="k">Status</td><td class="v">{{ ucfirst(strtolower($rm->status)) }}</td></tr>
        </table>
        <div class="quote">{{ $rm->grounds_detail }}</div>
        @if ($rm->approval_reasons)<div class="quote">{{ $rm->approval_reasons }}</div>@endif
    @endforeach
@endif

<h2>{{ ++$n }}. Nominee and legal heirs</h2>
@forelse ($a->nominees as $nom)
    <table class="kv">
        <tr><td class="k">Nominee</td><td class="v">{{ $nom->nominee_name }}</td></tr>
        <tr><td class="k">Relationship</td><td class="v">{{ $nom->relationship }}</td></tr>
        <tr><td class="k">CNIC</td><td class="v">{{ $nom->nominee_cnic ?: '—' }}</td></tr>
        <tr><td class="k">Form received</td><td class="v">{{ $fmt($nom->form_received_on) }}</td></tr>
    </table>
    @if ($nom->heirs->isNotEmpty())
        <table class="t">
            <thead><tr><th>#</th><th>Heir</th><th>Relationship</th><th>CNIC</th></tr></thead>
            <tbody>
            @foreach ($nom->heirs as $hh)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $hh->heir_name }}</td>
                    <td>{{ $hh->relationship }}</td>
                    <td>{{ $hh->cnic ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@empty
    <p class="muted">
        No nomination form on record. Under the proviso to Scheme para 3(iii)(B) the District
        Officer shall not regularize the possession until it is obtained.
    </p>
@endforelse

<h2>{{ ++$n }}. Decisions and approvals</h2>
@forelse ($a->approvals as $ap)
    <h3>{{ ucwords(strtolower(str_replace('_', ' ', $ap->level))) }}
        &mdash; {{ ucfirst(strtolower($ap->action)) }}
        @if (! $ap->is_within_sla) (beyond the statutory limit) @endif</h3>
    <table class="kv">
        <tr><td class="k">Acted on</td><td class="v">{{ $fmt($ap->acted_at, 'd-m-Y H:i') }}</td></tr>
        <tr><td class="k">Due by</td><td class="v">{{ $fmt($ap->due_by) }}</td></tr>
        @if ($ap->days_taken !== null)
            <tr><td class="k">Days taken</td><td class="v">{{ $ap->days_taken }}</td></tr>
        @endif
        @if ($ap->order_reference)
            <tr><td class="k">Order reference</td><td class="v">{{ $ap->order_reference }}</td></tr>
        @endif
    </table>
    <p><strong>Reasons recorded</strong></p>
    <div class="quote">{{ $ap->reasons }}</div>
    @if ($ap->conditions)
        <p><strong>Conditions</strong></p>
        <div class="quote">{{ $ap->conditions }}</div>
    @endif
@empty
    <p class="muted">No decision has been recorded.</p>
@endforelse

@if ($a->agreement)
    <h2>{{ ++$n }}. Tenancy agreement</h2>
    <table class="kv">
        <tr><td class="k">Agreement no.</td><td class="v">{{ $a->agreement->agreement_no }}</td></tr>
        <tr><td class="k">Executed on</td><td class="v">{{ $fmt($a->agreement->executed_on) }}</td></tr>
        <tr><td class="k">Monthly rent</td><td class="v">{{ $rs($a->agreement->monthly_rent) }}</td></tr>
        <tr><td class="k">Security</td>
            <td class="v">{{ $a->agreement->security_amount ? $rs($a->agreement->security_amount) : '—' }}</td></tr>
        <tr><td class="k">Status</td><td class="v">{{ ucfirst(strtolower($a->agreement->status)) }}</td></tr>
    </table>
@endif

@if ($a->order)
    <h2>{{ ++$n }}. Regularization order</h2>
    <table class="kv">
        <tr><td class="k">Order no.</td><td class="v">{{ $a->order->order_no }}</td></tr>
        <tr><td class="k">Dated</td><td class="v">{{ $fmt($a->order->order_date) }}</td></tr>
        <tr><td class="k">Area regularized</td>
            <td class="v">{{ $a->order->regularized_area_sqft
                ? number_format((float) $a->order->regularized_area_sqft, 2) . ' sqft' : '—' }}</td></tr>
    </table>
    <div class="quote">{{ $a->order->order_text }}</div>
@endif

<h2>{{ ++$n }}. Complete case history</h2>
<table class="t">
    <thead><tr><th>When</th><th>From</th><th>To</th><th>Role</th><th>Remarks</th></tr></thead>
    <tbody>
    @foreach ($a->history as $hh)
        <tr>
            <td>{{ $fmt($hh->occurred_at, 'd-m-Y H:i') }}</td>
            <td>{{ \App\Services\WorkflowService::LABELS[$hh->from_status] ?? ($hh->from_status ?: '—') }}</td>
            <td>{{ \App\Services\WorkflowService::LABELS[$hh->to_status] ?? $hh->to_status }}</td>
            <td>{{ $hh->actor_role ? ucwords(strtolower(str_replace('_', ' ', $hh->actor_role))) : '—' }}</td>
            <td>{{ $hh->remarks }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p class="faint">
    End of report &mdash; {{ $n }} sections. Scanned documents are held in the case file and are
    not reproduced here; each is listed with its SHA-256 digest so the copy on record can be
    identified.
</p>

@endsection
