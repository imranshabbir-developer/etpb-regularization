@extends('layouts.app')

@section('title', 'Occupants and litigation')
@section('heading', 'Occupants & litigation — ' . $application->application_no)

@section('content')

<div class="page-head">
    <h1>Rent offered by other occupants, and litigation</h1>
    <p class="lede">
        Anyone else in occupation of this property, what they offer, and whether a court is
        already seized of the matter.
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; Case file</a>
        <span class="badge badge-{{ $application->statusTone() }}">{{ $application->statusLabel() }}</span>
        @if ($application->is_sub_judice)
            <span class="badge badge-danger">Sub judice — parked</span>
        @endif
    </div>
</div>

@if ($application->is_sub_judice)
    <div class="alert alert-danger">
        @include('partials.icon', ['name' => 'gavel'])
        <div>
            <p class="mb-0">
                <strong>This application cannot proceed.</strong>
                A case is pending or a restraining order subsists. It will resume automatically
                once every case below is disposed of and no stay remains in force.
            </p>
        </div>
    </div>
@endif

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- Occupant offers ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Rent offered by occupants</h3>
                <span class="badge badge-neutral">{{ $offers->count() }}</span>
            </div>

            @if ($offers->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'users'])
                    <p class="mb-0">No competing offer recorded.</p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead>
                        <tr>
                            <th>Occupant</th><th>Portion</th>
                            <th class="num">Rent offered</th><th>Offered on</th><th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if ($assessed)
                            <tr style="background:var(--tint)">
                                <td colspan="2"><strong>Rent assessed by the District Officer</strong></td>
                                <td class="num"><strong>Rs. {{ number_format((float) $assessed, 2) }}</strong></td>
                                <td colspan="2" class="faint">for comparison</td>
                            </tr>
                        @endif
                        @foreach ($offers as $o)
                            <tr>
                                <td>
                                    {{ $o->occupant_name }}
                                    @if ($o->occupant_parentage)
                                        <div class="faint" style="font-size:.78rem">s/o {{ $o->occupant_parentage }}</div>
                                    @endif
                                    @if ($o->occupant_cnic)
                                        <div class="faint num" style="font-size:.75rem">{{ $o->occupant_cnic }}</div>
                                    @endif
                                    @if ($o->possession_since)
                                        <div class="faint" style="font-size:.75rem">
                                            in possession since
                                            {{ \Illuminate\Support\Carbon::parse($o->possession_since)->format('d-m-Y') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $o->portion_occupied ?: '—' }}
                                    @if ($o->area_sqft)
                                        <div class="faint" style="font-size:.78rem">
                                            {{ number_format((float) $o->area_sqft, 0) }} sqft
                                        </div>
                                    @endif
                                </td>
                                <td class="num">
                                    <strong>Rs. {{ number_format((float) $o->rent_offered, 2) }}</strong>
                                    @if ($assessed && (float) $o->rent_offered > (float) $assessed)
                                        <div><span class="badge badge-gold">Above assessment</span></div>
                                    @endif
                                </td>
                                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($o->offer_date)->format('d-m-Y') }}</td>
                                <td>
                                    @php $tone = match ($o->status) {
                                        'ACCEPTED' => 'good', 'REJECTED', 'WITHDRAWN' => 'danger', default => 'neutral',
                                    }; @endphp
                                    <span class="badge badge-{{ $tone }}">
                                        {{ ucwords(strtolower(str_replace('_', ' ', $o->status))) }}
                                    </span>
                                    @if ($o->remarks)
                                        <div class="faint" style="font-size:.75rem;max-width:220px">{{ $o->remarks }}</div>
                                    @endif

                                    @if ($o->status === 'RECORDED')
                                        @can('do', 'litigation.manage')
                                            <form method="POST" action="{{ route('occupancy.offers.decide', $o) }}"
                                                  style="margin-top:.4rem">
                                                @csrf
                                                <select name="status" class="select" style="font-size:.78rem;padding:.2rem .4rem">
                                                    <option value="UNDER_CONSIDERATION">Under consideration</option>
                                                    <option value="ACCEPTED">Accept</option>
                                                    <option value="REJECTED">Reject</option>
                                                    <option value="WITHDRAWN">Withdrawn</option>
                                                </select>
                                                <input type="text" name="remarks" class="input" required minlength="10"
                                                       style="font-size:.78rem;padding:.2rem .4rem;margin-top:.25rem"
                                                       placeholder="Reasons">
                                                <button class="btn btn-outline btn-sm" type="submit"
                                                        style="margin-top:.25rem">Record</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @can('do', 'litigation.manage')
                <div class="card-foot">
                    <form method="POST" action="{{ route('occupancy.offers.store', $application) }}">
                        @csrf
                        <div class="grid-3">
                            <div class="field">
                                <label for="occupant_name">Occupant <span class="req">*</span></label>
                                <input type="text" id="occupant_name" name="occupant_name" class="input" required>
                            </div>
                            <div class="field">
                                <label for="occupant_parentage">Parentage</label>
                                <input type="text" id="occupant_parentage" name="occupant_parentage" class="input">
                            </div>
                            <div class="field">
                                <label for="occupant_cnic">CNIC</label>
                                <input type="text" id="occupant_cnic" name="occupant_cnic" class="input"
                                       inputmode="numeric" maxlength="13" pattern="[0-9]{13}">
                            </div>
                            <div class="field">
                                <label for="occupant_contact">Contact</label>
                                <input type="text" id="occupant_contact" name="occupant_contact" class="input">
                            </div>
                            <div class="field">
                                <label for="portion_occupied">Portion occupied</label>
                                <input type="text" id="portion_occupied" name="portion_occupied" class="input">
                            </div>
                            <div class="field">
                                <label for="o_area">Area (sqft)</label>
                                <input type="text" id="o_area" name="area_sqft" class="input" inputmode="decimal">
                            </div>
                            <div class="field">
                                <label for="rent_offered">Rent offered (Rs.) <span class="req">*</span></label>
                                <input type="text" id="rent_offered" name="rent_offered" class="input"
                                       inputmode="decimal" required>
                            </div>
                            <div class="field">
                                <label for="offer_date">Offered on <span class="req">*</span></label>
                                <input type="date" id="offer_date" name="offer_date" class="input"
                                       value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="possession_since">In possession since</label>
                                <input type="date" id="possession_since" name="possession_since" class="input"
                                       max="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="field">
                            <label for="terms_offered">Terms offered</label>
                            <textarea id="terms_offered" name="terms_offered" class="textarea"
                                      style="min-height:60px"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) Record offer
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <div>
        {{-- ---------- Litigation ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Litigation</h3>
                <span class="badge badge-neutral">{{ $litigations->count() }}</span>
            </div>

            @if ($litigations->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'gavel'])
                    <p class="mb-0">No case on record.</p>
                </div>
            @else
                <div class="card-body">
                    @foreach ($litigations as $l)
                        <div class="inline-list">
                            <strong>{{ $l->case_no }}</strong>
                            @if ($l->is_pending)<span class="badge badge-warn">Pending</span>@endif
                            @if ($l->has_restraining_order)<span class="badge badge-danger">Restraining order</span>@endif
                            @if ($l->is_direction_case)<span class="badge badge-info">Direction case</span>@endif
                            @if (! $l->is_pending && ! $l->has_restraining_order)
                                <span class="badge badge-good">Disposed</span>
                            @endif
                        </div>

                        <dl class="kv mt-1">
                            <dt>Court</dt><dd>{{ $l->court_name }}</dd>
                            @if ($l->case_title)<dt>Title</dt><dd>{{ $l->case_title }}</dd>@endif
                            <dt>Type</dt><dd>{{ ucwords(strtolower(str_replace('_', ' ', $l->case_type))) }}</dd>
                            @if ($l->next_hearing_date)
                                <dt>Next hearing</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($l->next_hearing_date)->format('d-m-Y') }}</dd>
                            @endif
                            @if ($l->outcome && $l->outcome !== 'PENDING')
                                <dt>Outcome</dt><dd>{{ ucfirst(strtolower($l->outcome)) }}</dd>
                            @endif
                        </dl>

                        @if ($l->restraining_order_text)
                            <p class="muted" style="font-size:.84rem;white-space:pre-wrap">{{ $l->restraining_order_text }}</p>
                        @endif
                        @if ($l->direction_summary)
                            <p class="muted" style="font-size:.84rem;white-space:pre-wrap">{{ $l->direction_summary }}</p>
                        @endif

                        @can('do', 'litigation.manage')
                            <form method="POST" action="{{ route('occupancy.litigation.update', $l) }}" class="mt-1">
                                @csrf
                                <fieldset class="group">
                                    <legend>Update</legend>
                                    <div class="field" style="display:flex;gap:1rem;flex-wrap:wrap">
                                        <label style="font-weight:500">
                                            <input type="checkbox" name="is_pending" value="1" @checked($l->is_pending)>
                                            Still pending
                                        </label>
                                        <label style="font-weight:500">
                                            <input type="checkbox" name="has_restraining_order" value="1"
                                                   @checked($l->has_restraining_order)>
                                            Stay in force
                                        </label>
                                    </div>
                                    <div class="grid-2">
                                        <div class="field">
                                            <label for="nh_{{ $l->id }}">Next hearing</label>
                                            <input type="date" id="nh_{{ $l->id }}" name="next_hearing_date" class="input"
                                                   value="{{ $l->next_hearing_date?->toDateString() }}">
                                        </div>
                                        <div class="field">
                                            <label for="oc_{{ $l->id }}">Outcome</label>
                                            <select id="oc_{{ $l->id }}" name="outcome" class="select">
                                                @foreach (['PENDING' => 'Pending', 'ALLOWED' => 'Allowed',
                                                           'DISMISSED' => 'Dismissed', 'WITHDRAWN' => 'Withdrawn',
                                                           'COMPROMISED' => 'Compromised', 'REMANDED' => 'Remanded',
                                                           'ABATED' => 'Abated'] as $v => $lbl)
                                                    <option value="{{ $v }}" @selected($l->outcome === $v)>{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="lo_{{ $l->id }}">Last order</label>
                                        <textarea id="lo_{{ $l->id }}" name="last_order_summary" class="textarea"
                                                  style="min-height:60px">{{ $l->last_order_summary }}</textarea>
                                    </div>
                                    <button class="btn btn-outline btn-sm" type="submit">Update case</button>
                                </fieldset>
                            </form>
                        @endcan

                        @if (! $loop->last)<hr class="divider">@endif
                    @endforeach
                </div>
            @endif

            @can('do', 'litigation.manage')
                <div class="card-foot">
                    <form method="POST" action="{{ route('occupancy.litigation.store', $application) }}">
                        @csrf
                        <div class="field">
                            <label for="court_name">Court <span class="req">*</span></label>
                            <input type="text" id="court_name" name="court_name" class="input" required>
                        </div>
                        <div class="grid-2">
                            <div class="field">
                                <label for="case_no">Case no. <span class="req">*</span></label>
                                <input type="text" id="case_no" name="case_no" class="input" required>
                            </div>
                            <div class="field">
                                <label for="case_type">Type</label>
                                <select id="case_type" name="case_type" class="select">
                                    @foreach (['CIVIL_SUIT' => 'Civil suit', 'WRIT_PETITION' => 'Writ petition',
                                               'APPEAL' => 'Appeal', 'REVISION' => 'Revision',
                                               'EXECUTION' => 'Execution', 'CONTEMPT' => 'Contempt',
                                               'DIRECTION_CASE' => 'Direction case', 'OTHER' => 'Other'] as $v => $lbl)
                                        <option value="{{ $v }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="case_title">Case title</label>
                            <input type="text" id="case_title" name="case_title" class="input">
                        </div>
                        <div class="field" style="display:flex;gap:1rem;flex-wrap:wrap">
                            <label style="font-weight:500">
                                <input type="checkbox" name="is_pending" value="1" checked> Pending before the court
                            </label>
                            <label style="font-weight:500">
                                <input type="checkbox" name="has_restraining_order" value="1"> Restraining order
                            </label>
                            <label style="font-weight:500">
                                <input type="checkbox" name="is_direction_case" value="1"> Direction case
                            </label>
                        </div>
                        <div class="field">
                            <label for="restraining_order_text">Terms of the restraining order</label>
                            <textarea id="restraining_order_text" name="restraining_order_text"
                                      class="textarea" style="min-height:60px"></textarea>
                        </div>
                        <div class="field">
                            <label for="direction_summary">Direction given</label>
                            <textarea id="direction_summary" name="direction_summary"
                                      class="textarea" style="min-height:60px"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) Record case
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</div>

@endsection
