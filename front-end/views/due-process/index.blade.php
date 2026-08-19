@extends('layouts.app')

@section('title', 'Notices and objections')
@section('heading', 'Due process — ' . $application->application_no)

@section('content')

<div class="page-head">
    <h1>Notices, objections and hearings</h1>
    <p class="lede">
        The proposed assessment is made openly available, notice is given to the tenant and the
        general public, {{ $objectionDays }} days are allowed for objections, and the rent is fixed
        only after an opportunity of hearing.
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; Case file</a>
        <a href="{{ route('assessment.show', $application) }}" class="badge badge-neutral">Assessment</a>
        <span class="badge badge-{{ $application->statusTone() }}">{{ $application->statusLabel() }}</span>
        <span class="clause">Clause 10(i)(b)–(d)</span>
    </div>
</div>

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- Notices ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Notices issued</h3>
                <span class="badge badge-neutral">{{ $application->notices->count() }}</span>
            </div>

            @if ($application->notices->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'inbox'])
                    <p class="mb-0">No notice has issued.</p>
                    <p class="hint">
                        The {{ $slaDays }}-day assessment clock of Clause 10(i)(e) starts on the first notice.
                    </p>
                </div>
            @else
                <div class="table-wrap" style="border:0;border-radius:0">
                    <table class="data">
                        <thead>
                        <tr><th>Notice</th><th>Type</th><th>Issued</th><th>Service</th><th>Objections until</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($application->notices as $n)
                            @php $expired = \Illuminate\Support\Carbon::parse($n->objection_deadline)->isPast(); @endphp
                            <tr>
                                <td class="nowrap" style="font-size:.8rem">{{ $n->notice_no }}</td>
                                <td><span class="badge badge-neutral">{{ ucfirst(strtolower($n->notice_type)) }}</span></td>
                                <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($n->issued_on)->format('d-m-Y') }}</td>
                                <td class="faint" style="font-size:.8rem">
                                    {{ ucwords(strtolower(str_replace('_', ' ', $n->service_mode))) }}
                                    @if ($n->newspaper_name)<div>{{ $n->newspaper_name }}</div>@endif
                                </td>
                                <td class="nowrap">
                                    <span class="badge badge-{{ $expired ? 'neutral' : 'warn' }}">
                                        {{ \Illuminate\Support\Carbon::parse($n->objection_deadline)->format('d-m-Y') }}
                                    </span>
                                    @if (! $expired)
                                        <div class="faint" style="font-size:.74rem">window open</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @can('do', 'notices.issue')
                <div class="card-foot">
                    <form method="POST" action="{{ route('due-process.notices.store', $application) }}">
                        @csrf
                        <div class="grid-3">
                            <div class="field">
                                <label for="notice_type">Type</label>
                                <select id="notice_type" name="notice_type" class="select">
                                    <option value="PUBLIC">Public notice</option>
                                    <option value="TENANT">Notice to tenant / occupant</option>
                                    <option value="OBJECTOR">Notice to objector</option>
                                    <option value="HEARING">Notice of hearing</option>
                                    <option value="SHOW_CAUSE">Show cause</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="issued_on">Issued on</label>
                                <input type="date" id="issued_on" name="issued_on" class="input"
                                       value="{{ old('issued_on', now()->toDateString()) }}" required>
                            </div>
                            <div class="field">
                                <label for="served_on">Served on</label>
                                <input type="date" id="served_on" name="served_on" class="input"
                                       value="{{ old('served_on') }}">
                                <p class="hint">The {{ $objectionDays }} days run from receipt.</p>
                            </div>
                            <div class="field">
                                <label for="service_mode">Mode of service</label>
                                <select id="service_mode" name="service_mode" class="select">
                                    @foreach ([
                                        'HAND' => 'By hand', 'REGISTERED_POST' => 'Registered post',
                                        'COURIER' => 'Courier', 'NEWSPAPER' => 'Newspaper publication',
                                        'NOTICE_BOARD' => 'Notice board', 'AFFIXATION' => 'Affixation',
                                        'EMAIL' => 'Email', 'SMS' => 'SMS',
                                    ] as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label for="newspaper_name">Newspaper</label>
                                <input type="text" id="newspaper_name" name="newspaper_name" class="input">
                            </div>
                            <div class="field">
                                <label for="published_on">Published on</label>
                                <input type="date" id="published_on" name="published_on" class="input">
                            </div>
                        </div>
                        <div class="field">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="input"
                                   value="Proposed assessment of rent — {{ $application->property?->identity() }}">
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) Issue notice
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        {{-- ---------- Objections ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Objections</h3>
                <span class="badge badge-neutral">{{ $application->objections->count() }}</span>
                @php $open = $application->objections->whereIn('status', ['FILED', 'UNDER_HEARING'])->count(); @endphp
                @if ($open)
                    <span class="badge badge-warn">{{ $open }} undecided</span>
                @endif
            </div>

            @if ($application->objections->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'empty'])
                    <p class="mb-0">No objections filed.</p>
                </div>
            @else
                <div class="card-body">
                    @foreach ($application->objections as $o)
                        @php $d = $decisions->get($o->id); @endphp
                        <div style="padding-bottom:1rem">
                            <div class="inline-list">
                                <strong>{{ $o->objector_name }}</strong>
                                <span class="badge badge-{{ $o->status === 'DECIDED' ? 'good' : 'warn' }}">
                                    {{ ucfirst(strtolower($o->status)) }}
                                </span>
                                @unless ($o->is_within_time)
                                    <span class="badge badge-danger">Filed out of time</span>
                                @endunless
                                <span class="faint" style="font-size:.78rem">{{ $o->objection_no }}</span>
                            </div>

                            <dl class="kv mt-1" style="font-size:.83rem">
                                @if ($o->relationship_to_property)
                                    <dt>Relationship</dt><dd>{{ $o->relationship_to_property }}</dd>
                                @endif
                                <dt>Filed on</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($o->filed_on)->format('d-m-Y') }}</dd>
                            </dl>

                            <p class="muted" style="white-space:pre-wrap;font-size:.86rem;margin:.5rem 0">{{ $o->plea }}</p>

                            @if ($d)
                                <div class="alert alert-{{ $d->decision === 'ACCEPTED' ? 'good' : ($d->decision === 'REJECTED' ? 'danger' : 'warn') }}"
                                     style="margin:.5rem 0 0">
                                    @include('partials.icon', ['name' => 'check'])
                                    <div>
                                        <strong>{{ ucwords(strtolower(str_replace('_', ' ', $d->decision))) }}</strong>
                                        <p class="mb-0" style="white-space:pre-wrap">{{ $d->reasons }}</p>
                                    </div>
                                </div>
                            @else
                                @can('do', 'objections.decide')
                                    <form method="POST" action="{{ route('objections.decide', $o) }}" class="mt-1">
                                        @csrf
                                        <div class="grid-2">
                                            <div class="field">
                                                <label for="decision_{{ $o->id }}">Decision</label>
                                                <select id="decision_{{ $o->id }}" name="decision" class="select">
                                                    <option value="REJECTED">Rejected</option>
                                                    <option value="ACCEPTED">Accepted</option>
                                                    <option value="PARTIALLY_ACCEPTED">Partially accepted</option>
                                                    <option value="WITHDRAWN">Withdrawn</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="impact_{{ $o->id }}">Effect on rent (Rs.)</label>
                                                <input type="text" id="impact_{{ $o->id }}" name="rent_impact"
                                                       class="input" inputmode="decimal">
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="reasons_{{ $o->id }}">Reasons <span class="req">*</span></label>
                                            <textarea id="reasons_{{ $o->id }}" name="reasons" class="textarea"
                                                      style="min-height:70px" required minlength="30"></textarea>
                                        </div>
                                        <button class="btn btn-outline btn-sm" type="submit">Decide objection</button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                        @if (! $loop->last)<hr class="divider">@endif
                    @endforeach
                </div>
            @endif

            @can('do', 'objections.record')
                <div class="card-foot">
                    <form method="POST" action="{{ route('due-process.objections.store', $application) }}">
                        @csrf
                        <div class="grid-3">
                            <div class="field">
                                <label for="objector_name">Objector</label>
                                <input type="text" id="objector_name" name="objector_name" class="input" required>
                            </div>
                            <div class="field">
                                <label for="objector_parentage">Parentage</label>
                                <input type="text" id="objector_parentage" name="objector_parentage" class="input">
                            </div>
                            <div class="field">
                                <label for="objector_cnic">CNIC</label>
                                <input type="text" id="objector_cnic" name="objector_cnic" class="input"
                                       inputmode="numeric" maxlength="13" pattern="[0-9]{13}">
                            </div>
                            <div class="field">
                                <label for="objector_contact">Contact</label>
                                <input type="text" id="objector_contact" name="objector_contact" class="input">
                            </div>
                            <div class="field">
                                <label for="relationship_to_property">Relationship to property</label>
                                <input type="text" id="relationship_to_property" name="relationship_to_property" class="input">
                            </div>
                            <div class="field">
                                <label for="filed_on">Filed on</label>
                                <input type="date" id="filed_on" name="filed_on" class="input"
                                       value="{{ now()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="field">
                            <label for="plea">Plea <span class="req">*</span></label>
                            <textarea id="plea" name="plea" class="textarea" required minlength="20"
                                      placeholder="Record the objection in the objector's own terms."></textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) Record objection
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    <div>
        {{-- ---------- Hearings ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Hearings</h3>
                <span class="badge badge-neutral">{{ $application->hearings->count() }}</span>
            </div>

            @if ($application->hearings->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'clock'])
                    <p class="mb-0">No hearing scheduled.</p>
                    <p class="hint">Clause 10(i)(d) requires an opportunity of hearing before rent is fixed.</p>
                </div>
            @else
                <div class="card-body">
                    @foreach ($application->hearings as $h)
                        <div class="inline-list">
                            <strong>{{ \Illuminate\Support\Carbon::parse($h->scheduled_for)->format('d-m-Y H:i') }}</strong>
                            <span class="badge badge-{{ $h->status === 'HELD' ? 'good' : ($h->status === 'SCHEDULED' ? 'info' : 'warn') }}">
                                {{ ucfirst(strtolower($h->status)) }}
                            </span>
                        </div>
                        @if ($h->venue)<div class="faint" style="font-size:.8rem">{{ $h->venue }}</div>@endif

                        @if ($h->proceedings)
                            <p class="muted" style="white-space:pre-wrap;font-size:.85rem;margin:.5rem 0">{{ $h->proceedings }}</p>
                        @elseif (auth()->user()->hasPermission('hearings.record'))
                            <form method="POST" action="{{ route('hearings.record', $h) }}" class="mt-1">
                                @csrf
                                <div class="field">
                                    <label for="proceedings_{{ $h->id }}">Proceedings <span class="req">*</span></label>
                                    <textarea id="proceedings_{{ $h->id }}" name="proceedings" class="textarea"
                                              style="min-height:90px" required minlength="20"></textarea>
                                </div>
                                <div class="field">
                                    <label for="attendance_{{ $h->id }}">Attendance (one per line)</label>
                                    <textarea id="attendance_{{ $h->id }}" name="attendance" class="textarea"
                                              style="min-height:60px"></textarea>
                                </div>
                                <div class="field">
                                    <label for="hstatus_{{ $h->id }}">Outcome</label>
                                    <select id="hstatus_{{ $h->id }}" name="status" class="select">
                                        <option value="HELD">Held</option>
                                        <option value="ADJOURNED">Adjourned</option>
                                        <option value="CANCELLED">Cancelled</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="adj_{{ $h->id }}">Adjourned to</label>
                                    <input type="date" id="adj_{{ $h->id }}" name="adjourned_to" class="input">
                                </div>
                                <button class="btn btn-outline btn-sm" type="submit">Record proceedings</button>
                            </form>
                        @endif
                        @if (! $loop->last)<hr class="divider">@endif
                    @endforeach
                </div>
            @endif

            @can('do', 'hearings.schedule')
                <div class="card-foot">
                    <form method="POST" action="{{ route('due-process.hearings.store', $application) }}">
                        @csrf
                        <div class="field">
                            <label for="scheduled_for">Date and time</label>
                            <input type="datetime-local" id="scheduled_for" name="scheduled_for" class="input" required>
                        </div>
                        <div class="field">
                            <label for="venue">Venue</label>
                            <input type="text" id="venue" name="venue" class="input"
                                   value="ETPB District Office, {{ $application->district?->name ?? '' }}">
                        </div>
                        <div class="field">
                            <label for="parties_summoned">Parties summoned (one per line)</label>
                            <textarea id="parties_summoned" name="parties_summoned" class="textarea"
                                      style="min-height:70px">{{ $application->applicant?->full_name }}</textarea>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">
                            @include('partials.icon', ['name' => 'plus']) Schedule hearing
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="card">
            <div class="card-head"><h3>Where this leads</h3></div>
            <div class="card-body">
                <ul class="timeline">
                    <li class="{{ $application->notices->isNotEmpty() ? 'is-done' : '' }}">
                        <div class="t-title">Notice issued</div>
                        <div class="t-meta">Clause 10(i)(b)–(c) &middot; starts the {{ $slaDays }}-day clock</div>
                    </li>
                    <li class="{{ $application->notices->isNotEmpty() && \Illuminate\Support\Carbon::parse($application->notices->first()->objection_deadline)->isPast() ? 'is-done' : '' }}">
                        <div class="t-title">{{ $objectionDays }}-day objection window closes</div>
                        <div class="t-meta">Clause 10(i)(c)</div>
                    </li>
                    <li class="{{ $application->objections->isNotEmpty() && $application->objections->where('status','DECIDED')->count() === $application->objections->count() ? 'is-done' : '' }}">
                        <div class="t-title">Objections heard and decided</div>
                        <div class="t-meta">Clause 10(i)(d)</div>
                    </li>
                    <li class="{{ $application->rent_fixed_at ? 'is-done' : 'is-current' }}">
                        <div class="t-title">Rent fixed for reasons</div>
                        <div class="t-meta">
                            <a href="{{ route('assessment.show', $application) }}">Go to assessment &rarr;</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
