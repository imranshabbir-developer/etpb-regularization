@extends('layouts.app')

@section('title', __('Occupants and litigation'))
@section('heading', __('Occupants & litigation — :no', ['no' => $application->application_no]))

@section('content')

<div class="page-head">
    <h1>{{ __('Rent offered by other occupants, and litigation') }}</h1>
    <p class="lede">
        {{ __('Anyone else in occupation of this property, what they offer, and whether a court is already seized of the matter.') }}
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; {{ __('Case file') }}</a>
        <span class="badge badge-{{ $application->statusTone() }}">{{ __($application->statusLabel()) }}</span>
        @if ($application->is_sub_judice)
            <span class="badge badge-danger">{{ __('Sub judice — parked') }}</span>
        @endif
    </div>
</div>

@if ($application->is_sub_judice)
    <div class="alert alert-danger">
        @include('partials.icon', ['name' => 'gavel'])
        <div>
            <p class="mb-0">
                <strong>{{ __('This application cannot proceed.') }}</strong>
                {{ __('A case is pending or a restraining order subsists. It will resume automatically once every case below is disposed of and no stay remains in force.') }}
            </p>
        </div>
    </div>
@endif

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- Occupant offers ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Rent offered by occupants') }}</h3>
                <span class="badge badge-neutral">{{ $offers->count() }}</span>
            </div>

            @if ($offers->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'users'])
                    <p class="mb-0">{{ __('No competing offer recorded.') }}</p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead>
                        <tr>
                            <th>{{ __('Occupant') }}</th><th>{{ __('Portion') }}</th>
                            <th class="num">{{ __('Rent offered') }}</th><th>{{ __('Offered on') }}</th><th>{{ __('Status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if ($assessed)
                            <tr style="background:var(--tint)">
                                <td colspan="2"><strong>{{ __('Rent assessed by the District Officer') }}</strong></td>
                                <td class="num"><strong>Rs. {{ number_format((float) $assessed, 2) }}</strong></td>
                                <td colspan="2" class="faint">{{ __('for comparison') }}</td>
                            </tr>
                        @endif
                        @foreach ($offers as $o)
                            <tr>
                                <td>
                                    {{ $o->occupant_name }}
                                    @if ($o->occupant_parentage)
                                        <div class="faint" style="font-size:.78rem">{{ __('s/o :name', ['name' => $o->occupant_parentage]) }}</div>
                                    @endif
                                    @if ($o->occupant_cnic)
                                        <div class="faint num" style="font-size:.75rem">{{ $o->occupant_cnic }}</div>
                                    @endif
                                    @if ($o->possession_since)
                                        <div class="faint" style="font-size:.75rem">
                                            {{ __('in possession since :date', ['date' => \Illuminate\Support\Carbon::parse($o->possession_since)->translatedFormat('d-m-Y')]) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $o->portion_occupied ?: '—' }}
                                    @if ($o->area_sqft)
                                        <div class="faint" style="font-size:.78rem">
                                            {{ __(':area sqft', ['area' => number_format((float) $o->area_sqft, 0)]) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="num">
                                    <strong>Rs. {{ number_format((float) $o->rent_offered, 2) }}</strong>
                                    @if ($assessed && (float) $o->rent_offered > (float) $assessed)
                                        <div><span class="badge badge-gold">{{ __('Above assessment') }}</span></div>
                                    @endif
                                </td>
                                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($o->offer_date)->translatedFormat('d-m-Y') }}</td>
                                <td>
                                    @php $tone = match ($o->status) {
                                        'ACCEPTED' => 'good', 'REJECTED', 'WITHDRAWN' => 'danger', default => 'neutral',
                                    }; @endphp
                                    <span class="badge badge-{{ $tone }}">
                                        {{ __(ucwords(strtolower(str_replace('_', ' ', $o->status)))) }}
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
                                                    <option value="UNDER_CONSIDERATION">{{ __('Under consideration') }}</option>
                                                    <option value="ACCEPTED">{{ __('Accept') }}</option>
                                                    <option value="REJECTED">{{ __('Reject') }}</option>
                                                    <option value="WITHDRAWN">{{ __('Withdrawn') }}</option>
                                                </select>
                                                <input type="text" name="remarks" class="input" required minlength="10"
                                                       style="font-size:.78rem;padding:.2rem .4rem;margin-top:.25rem"
                                                       placeholder="{{ __('Reasons') }}">
                                                <button class="btn btn-outline btn-sm" type="submit"
                                                        style="margin-top:.25rem">{{ __('Record') }}</button>
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
                                <label for="occupant_name">{{ __('Occupant') }} <span class="req">*</span></label>
                                <input type="text" id="occupant_name" name="occupant_name" class="input" required>
                            </div>
                            <div class="field">
                                <label for="occupant_parentage">{{ __('Parentage') }}</label>
                                <input type="text" id="occupant_parentage" name="occupant_parentage" class="input">
                            </div>
                            <div class="field">
                                <label for="occupant_cnic">{{ __('CNIC') }}</label>
                                <input type="text" id="occupant_cnic" name="occupant_cnic" class="input"
                                       inputmode="numeric" maxlength="13" pattern="[0-9]{13}">
                            </div>
                            <div class="field">
                                <label for="occupant_contact">{{ __('Contact') }}</label>
                                <input type="text" id="occupant_contact" name="occupant_contact" class="input">
                            </div>
                            <div class="field">
                                <label for="portion_occupied">{{ __('Portion occupied') }}</label>
                                <input type="text" id="portion_occupied" name="portion_occupied" class="input">
                            </div>
                            <div class="field">
                                <label for="o_area">{{ __('Area (sqft)') }}</label>
                                <input type="text" id="o_area" name="area_sqft" class="input" inputmode="decimal">
                            </div>
                            <div class="field">
                                <label for="rent_offered">{{ __('Rent offered (Rs.)') }} <span class="req">*</span></label>
                                <input type="text" id="rent_offered" name="rent_offered" class="input"
                                       inputmode="decimal" required>
                            </div>
                            <div class="field">
                                <label for="offer_date">{{ __('Offered on') }} <span class="req">*</span></label>
                                <input type="date" id="offer_date" name="offer_date" class="input"
                                       value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="possession_since">{{ __('In possession since') }}</label>
                                <input type="date" id="possession_since" name="possession_since" class="input"
                                       max="{{ now()->toDateString() }}">
                            </div>
                        </div>
                        <div class="field">
                            <label for="terms_offered">{{ __('Terms offered') }}</label>
                            <textarea id="terms_offered" name="terms_offered" class="textarea"
                                      style="min-height:60px"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) {{ __('Record offer') }}
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
                <h3>{{ __('Litigation') }}</h3>
                <span class="badge badge-neutral">{{ $litigations->count() }}</span>
            </div>

            @if ($litigations->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'gavel'])
                    <p class="mb-0">{{ __('No case on record.') }}</p>
                </div>
            @else
                <div class="card-body">
                    @foreach ($litigations as $l)
                        <div class="inline-list">
                            <strong>{{ $l->case_no }}</strong>
                            @if ($l->is_pending)<span class="badge badge-warn">{{ __('Pending') }}</span>@endif
                            @if ($l->has_restraining_order)<span class="badge badge-danger">{{ __('Restraining order') }}</span>@endif
                            @if ($l->is_direction_case)<span class="badge badge-info">{{ __('Direction case') }}</span>@endif
                            @if (! $l->is_pending && ! $l->has_restraining_order)
                                <span class="badge badge-good">{{ __('Disposed') }}</span>
                            @endif
                        </div>

                        <dl class="kv mt-1">
                            <dt>{{ __('Court') }}</dt><dd>{{ $l->court_name }}</dd>
                            @if ($l->case_title)<dt>{{ __('Title') }}</dt><dd>{{ $l->case_title }}</dd>@endif
                            <dt>{{ __('Type') }}</dt><dd>{{ __(ucwords(strtolower(str_replace('_', ' ', $l->case_type)))) }}</dd>
                            @if ($l->next_hearing_date)
                                <dt>{{ __('Next hearing') }}</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($l->next_hearing_date)->translatedFormat('d-m-Y') }}</dd>
                            @endif
                            @if ($l->outcome && $l->outcome !== 'PENDING')
                                <dt>{{ __('Outcome') }}</dt><dd>{{ __(ucfirst(strtolower($l->outcome))) }}</dd>
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
                                    <legend>{{ __('Update') }}</legend>
                                    <div class="field" style="display:flex;gap:1rem;flex-wrap:wrap">
                                        <label style="font-weight:500">
                                            <input type="checkbox" name="is_pending" value="1" @checked($l->is_pending)>
                                            {{ __('Still pending') }}
                                        </label>
                                        <label style="font-weight:500">
                                            <input type="checkbox" name="has_restraining_order" value="1"
                                                   @checked($l->has_restraining_order)>
                                            {{ __('Stay in force') }}
                                        </label>
                                    </div>
                                    <div class="grid-2">
                                        <div class="field">
                                            <label for="nh_{{ $l->id }}">{{ __('Next hearing') }}</label>
                                            <input type="date" id="nh_{{ $l->id }}" name="next_hearing_date" class="input"
                                                   value="{{ $l->next_hearing_date?->toDateString() }}">
                                        </div>
                                        <div class="field">
                                            <label for="oc_{{ $l->id }}">{{ __('Outcome') }}</label>
                                            <select id="oc_{{ $l->id }}" name="outcome" class="select">
                                                @foreach (['PENDING' => __('Pending'), 'ALLOWED' => __('Allowed'),
                                                           'DISMISSED' => __('Dismissed'), 'WITHDRAWN' => __('Withdrawn'),
                                                           'COMPROMISED' => __('Compromised'), 'REMANDED' => __('Remanded'),
                                                           'ABATED' => __('Abated')] as $v => $lbl)
                                                    <option value="{{ $v }}" @selected($l->outcome === $v)>{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="lo_{{ $l->id }}">{{ __('Last order') }}</label>
                                        <textarea id="lo_{{ $l->id }}" name="last_order_summary" class="textarea"
                                                  style="min-height:60px">{{ $l->last_order_summary }}</textarea>
                                    </div>
                                    <button class="btn btn-outline btn-sm" type="submit">{{ __('Update case') }}</button>
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
                            <label for="court_name">{{ __('Court') }} <span class="req">*</span></label>
                            <input type="text" id="court_name" name="court_name" class="input" required>
                        </div>
                        <div class="grid-2">
                            <div class="field">
                                <label for="case_no">{{ __('Case no.') }} <span class="req">*</span></label>
                                <input type="text" id="case_no" name="case_no" class="input" required>
                            </div>
                            <div class="field">
                                <label for="case_type">{{ __('Type') }}</label>
                                <select id="case_type" name="case_type" class="select">
                                    @foreach (['CIVIL_SUIT' => __('Civil suit'), 'WRIT_PETITION' => __('Writ petition'),
                                               'APPEAL' => __('Appeal'), 'REVISION' => __('Revision'),
                                               'EXECUTION' => __('Execution'), 'CONTEMPT' => __('Contempt'),
                                               'DIRECTION_CASE' => __('Direction case'), 'OTHER' => __('Other')] as $v => $lbl)
                                        <option value="{{ $v }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="case_title">{{ __('Case title') }}</label>
                            <input type="text" id="case_title" name="case_title" class="input">
                        </div>
                        <div class="field" style="display:flex;gap:1rem;flex-wrap:wrap">
                            <label style="font-weight:500">
                                <input type="checkbox" name="is_pending" value="1" checked> {{ __('Pending before the court') }}
                            </label>
                            <label style="font-weight:500">
                                <input type="checkbox" name="has_restraining_order" value="1"> {{ __('Restraining order') }}
                            </label>
                            <label style="font-weight:500">
                                <input type="checkbox" name="is_direction_case" value="1"> {{ __('Direction case') }}
                            </label>
                        </div>
                        <div class="field">
                            <label for="restraining_order_text">{{ __('Terms of the restraining order') }}</label>
                            <textarea id="restraining_order_text" name="restraining_order_text"
                                      class="textarea" style="min-height:60px"></textarea>
                        </div>
                        <div class="field">
                            <label for="direction_summary">{{ __('Direction given') }}</label>
                            <textarea id="direction_summary" name="direction_summary"
                                      class="textarea" style="min-height:60px"></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) {{ __('Record case') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</div>

@endsection
