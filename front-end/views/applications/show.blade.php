@extends('layouts.app')

@section('title', $application->application_no)
@section('heading', 'Case file — ' . $application->application_no)

@section('content')

    @php
        $a = $application;
        $p = $a->property;
        $area = $p?->currentArea;
        $poss = $a->possession;
    @endphp

    <div class="page-head">
        <h1>{{ $a->application_no }}</h1>
        <p class="lede">
            {{ $a->applicant?->nameWithParentage() }} &mdash; {{ $p?->identity() }},
            {{ $p?->district?->name }}
        </p>
        <div class="inline-list mt-1">
            <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">
                Payment {{ $a->payment_status }}
            </span>
            <span class="badge badge-{{ $a->statusTone() }}">{{ $a->statusLabel() }}</span>
            @if ($a->is_sub_judice)
                <span class="badge badge-warn">Sub judice</span>
            @endif
            @if ($poss?->is_eligible)
                <span class="badge badge-good">Eligible — Clause 3(ii)(a)</span>
            @elseif ($poss)
                <span class="badge badge-danger">Ineligible — Clause 3(ii)(a)</span>
            @endif
        </div>
    </div>

    <div class="soft-panel">
        <p><strong>This is the main case page.</strong> Use the quick links below to open documents, deposit, rent assessment, objections, litigation, arrears, completion, and reports for this application.</p>
    </div>

    {{-- ---------- Module navigation ---------- --}}
    <div class="card">
        <div class="card-head"><h3>Open a section</h3></div>
        <div class="card-body tight">
            <div class="btn-row">
                @can('do', 'documents.view')
                    <a href="{{ route('documents.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'file']) Evidence
                        <span class="badge badge-neutral">{{ $a->documents->count() }}</span>
                    </a>
                @endcan
                @if (auth()->user()->hasAnyPermission('fee.view', 'fee.record', 'fee.verify'))
                    <a href="{{ route('fee.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'cash']) Fee
                        <span class="badge badge-{{ $a->payment_status === 'PAID' ? 'good' : 'warn' }}">
                            {{ $a->payment_status }}
                        </span>
                    </a>
                @endif
                @can('do', 'assessment.view')
                    <a href="{{ route('assessment.show', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'scale']) Rent assessment
                    </a>
                @endcan
                @can('do', 'notices.view')
                    <a href="{{ route('due-process.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'inbox']) Notices &amp; objections
                        @if ($a->objections->whereIn('status', ['FILED', 'UNDER_HEARING'])->count())
                            <span class="badge badge-warn">{{ $a->objections->whereIn('status', ['FILED', 'UNDER_HEARING'])->count() }}</span>
                        @endif
                    </a>
                @endcan
                @can('do', 'litigation.view')
                    <a href="{{ route('occupancy.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'gavel']) Occupants &amp; litigation
                    </a>
                @endcan
                @if (auth()->user()->hasAnyPermission('nominees.manage', 'agreements.execute', 'orders.issue'))
                    <a href="{{ route('completion.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'check']) Completion
                    </a>
                @endif
                @can('do', 'reports.deep')
                    <a href="{{ route('reports.deep', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'chart']) Deep report
                    </a>
                @endcan
                @can('do', 'arrears.view')
                    <a href="{{ route('arrears.index', $a) }}" class="btn btn-outline btn-sm">
                        @include('partials.icon', ['name' => 'cash']) Arrears
                        @if ((float) $a->arrears_balance > 0)
                            <span class="badge badge-danger">Rs. {{ number_format((float) $a->arrears_balance, 0) }}</span>
                        @endif
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- ---------- Statutory clocks ---------- --}}
    @if ($assessmentSla['applies'] || $approvalSla['applies'])
        <div class="tiles">
            @if ($assessmentSla['applies'])
                <div class="tile {{ $assessmentSla['tone'] === 'danger' ? 'is-danger' : ($assessmentSla['tone'] === 'warn' ? 'is-warn' : '') }}">
                    <div class="tile-label">Assessment deadline <span class="clause">Cl. 10(i)(e)</span></div>
                    <div class="tile-value" style="font-size:1.1rem">{{ $assessmentSla['label'] }}</div>
                    <div class="sla mt-1">
                        <div class="sla-bar">
                            <div class="sla-fill {{ $assessmentSla['tone'] === 'danger' ? 'is-danger' : ($assessmentSla['tone'] === 'warn' ? 'is-warn' : '') }}"
                                 style="width:{{ $assessmentSla['pct'] }}%"></div>
                        </div>
                    </div>
                    <div class="tile-sub">Due {{ \Illuminate\Support\Carbon::parse($assessmentSla['due'])->format('d-m-Y') }}</div>
                </div>
            @endif

            @if ($approvalSla['applies'])
                <div class="tile {{ $approvalSla['tone'] === 'danger' ? 'is-danger' : ($approvalSla['tone'] === 'warn' ? 'is-warn' : '') }}">
                    <div class="tile-label">Administrator approval <span class="clause">Cl. 3(ii)(d)</span></div>
                    <div class="tile-value" style="font-size:1.1rem">{{ $approvalSla['label'] }}</div>
                    <div class="sla mt-1">
                        <div class="sla-bar">
                            <div class="sla-fill {{ $approvalSla['tone'] === 'danger' ? 'is-danger' : ($approvalSla['tone'] === 'warn' ? 'is-warn' : '') }}"
                                 style="width:{{ $approvalSla['pct'] }}%"></div>
                        </div>
                    </div>
                    <div class="tile-sub">Due {{ \Illuminate\Support\Carbon::parse($approvalSla['due'])->format('d-m-Y') }}</div>
                </div>
            @endif
        </div>
    @endif

    <div class="grid-2" style="gap:1.15rem;align-items:start">
        <div>
            {{-- ---------- Applicant ---------- --}}
            <div class="card">
                <div class="card-head"><h3>Applicant</h3></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Name</dt><dd>{{ $a->applicant?->full_name }}</dd>
                        <dt>{{ $a->applicant?->parentage_type === 'HUSBAND' ? 'Husband' : 'Father' }}</dt>
                        <dd>{{ $a->applicant?->parentage_name }}</dd>
                        <dt>CNIC</dt><dd class="num">{{ $a->applicant?->maskedCnic() }}</dd>
                        <dt>Contact</dt><dd>{{ $a->applicant?->contact }}</dd>
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

            {{-- ---------- Property and area ---------- --}}
            <div class="card">
                <div class="card-head"><h3>Property</h3></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Property no.</dt><dd>{{ $p?->property_no }}</dd>
                        @if ($p?->sub_unit_no)
                            <dt>Sub-unit no.</dt><dd>{{ $p->sub_unit_no }}</dd>
                        @endif
                        <dt>Type / usage</dt><dd>{{ $p?->typeLabel() }} &mdash; {{ $p?->usageLabel() }}</dd>
                        <dt>Address</dt><dd>{{ $p?->address }}</dd>
                        <dt>Location</dt><dd>{{ $p?->locationChain() }}</dd>
                        @if ($p?->khasra_no)
                            <dt>Khasra no.</dt><dd>{{ $p->khasra_no }}</dd>
                        @endif
                        @if ($p?->primaryGeoTag)
                            <dt>Geo coordinates</dt>
                            <dd class="num">{{ $p->primaryGeoTag->latitude }}, {{ $p->primaryGeoTag->longitude }}</dd>
                        @endif
                    </dl>

                    @if ($area)
                        <hr class="divider">
                        <h4>Area</h4>
                        <dl class="kv">
                            <dt>Canonical area</dt>
                            <dd><strong>{{ number_format((float) $area->area_sqft, 2) }} sqft</strong></dd>
                            <dt>Standard applied</dt>
                            <dd>{{ $a->unitProfile?->name }}</dd>
                        </dl>

                        @php $trace = is_array($area->conversion_trace) ? $area->conversion_trace : json_decode((string) $area->conversion_trace, true); @endphp
                        @if (! empty($trace['components']))
                            <div class="table-wrap mt-1">
                                <table class="data">
                                    <thead>
                                    <tr><th>Entered</th><th class="num">Factor (sqft)</th><th class="num">Subtotal (sqft)</th></tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($trace['components'] as $c)
                                        <tr>
                                            <td>{{ rtrim(rtrim($c['quantity'], '0'), '.') }} {{ $c['unit_name'] }}</td>
                                            <td class="num">{{ rtrim(rtrim($c['sqft_per_unit'], '0'), '.') }}</td>
                                            <td class="num">{{ number_format((float) $c['subtotal_sqft'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="hint mt-1">
                                The factors above are frozen against this application, so a later change to
                                the conversion table cannot restate an assessment already made.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- ---------- Possession ---------- --}}
            @if ($poss)
                <div class="card">
                    <div class="card-head">
                        <h3>Possession</h3>
                        <div class="card-actions"><span class="clause">Clause 3(ii)(a)&ndash;(b)</span></div>
                    </div>
                    <div class="card-body">
                        <dl class="kv">
                            <dt>Date of possession</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($poss->date_of_possession)->format('d-m-Y') }}</dd>
                            <dt>Cut-off applied</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($poss->cutoff_applied)->format('d-m-Y') }}</dd>
                            <dt>Arrears run from</dt>
                            <dd>
                                <strong>{{ \Illuminate\Support\Carbon::parse($poss->arrears_from)->format('d-m-Y') }}</strong>
                                <span class="badge badge-neutral">{{ str_replace('_', ' ', $poss->arrears_from_basis) }}</span>
                            </dd>
                            @if ($poss->date_of_judicial_verdict)
                                <dt>Judicial verdict</dt>
                                <dd>{{ \Illuminate\Support\Carbon::parse($poss->date_of_judicial_verdict)->format('d-m-Y') }}</dd>
                            @endif
                        </dl>
                        <div class="alert {{ $poss->is_eligible ? 'alert-good' : 'alert-danger' }} mt-2">
                            @include('partials.icon', ['name' => $poss->is_eligible ? 'check' : 'alert'])
                            <div><p class="mb-0">{{ $poss->eligibility_reason }}</p></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ---------- Rent milestone grid ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>Rent assessment</h3>
                    <div class="card-actions">
                        <span class="clause">Clause 10</span>
                        <span class="clause">Clause 11(ii) &mdash; 8% p.a.</span>
                    </div>
                </div>

                @if (collect($milestones)->every(fn ($m) => ! $m['in_scope']))
                    <div class="empty">
                        @include('partials.icon', ['name' => 'scale'])
                        <p class="mb-0">Rent has not been assessed yet.</p>
                        <p class="hint">
                            The District Officer fixes the rent after the objection window and hearing.
                        </p>
                    </div>
                @else
                    <div class="table-wrap" style="border:0;border-radius:0">
                        <table class="data">
                            <thead>
                            <tr>
                                <th>Rent year</th>
                                <th>Period</th>
                                <th class="num">Monthly rent</th>
                                <th class="num">Annual rent</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($milestones as $m)
                                <tr class="{{ $m['in_scope'] ? 'is-milestone' : 'row-muted' }}">
                                    <td><strong>{{ $m['year'] }}</strong></td>
                                    <td class="nowrap">{{ $m['period'] }}</td>
                                    <td class="num">
                                        {{ $m['monthly_rent'] ? 'Rs. ' . number_format((float) $m['monthly_rent'], 2) : '—' }}
                                    </td>
                                    <td class="num">
                                        {{ $m['annual_rent'] ? 'Rs. ' . number_format((float) $m['annual_rent'], 2) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-foot">
                        <p class="mb-0 faint" style="font-size:.8rem">
                            The milestone years above are a presentation grid over the year-by-year ledger.
                            The six-year re-assessment cycle of Clause 11(i) governs the underlying computation.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <div>
            {{-- ---------- Actions ---------- --}}
            <div class="card">
                <div class="card-head"><h3>What can happen next</h3></div>
                <div class="card-body">
                    <p class="hint mt-0">
                        Available actions are enabled below. If an action is blocked, the system explains why.
                    </p>
                    @forelse ($nextStates as $state)
                        <form method="POST" action="{{ route('applications.transition', $a) }}" class="mb-0">
                            @csrf
                            <input type="hidden" name="to" value="{{ $state['to'] }}">

                            @if ($state['check']['allowed'])
                                <div class="field">
                                    <label for="remarks_{{ $state['to'] }}">Remarks (optional unless office policy requires them)</label>
                                    <textarea id="remarks_{{ $state['to'] }}" name="remarks" class="textarea"
                                              style="min-height:64px" maxlength="2000"></textarea>
                                </div>
                                <button class="btn btn-primary" type="submit" style="width:100%">
                                    @include('partials.icon', ['name' => 'check'])
                                    Move to {{ $state['label'] }}
                                </button>
                            @else
                                <button class="btn btn-outline" type="button" aria-disabled="true" style="width:100%">
                                    Move to {{ $state['label'] }}
                                </button>
                                <div class="alert alert-warn mt-1" style="margin-bottom:0">
                                    @include('partials.icon', ['name' => 'alert'])
                                    <div>
                                        <ul style="margin:0;padding-inline-start:1.05rem">
                                            @foreach ($state['check']['reasons'] as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </form>
                        @if (! $loop->last)<hr class="divider">@endif
                    @empty
                        <p class="muted mb-0">
                            This application is closed. No further transitions are available.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- ---------- Arrears ---------- --}}
            <div class="card">
                <div class="card-head">
                    <h3>Arrears summary</h3>
                    <div class="card-actions"><span class="clause">Clause 3(ii)(b)</span></div>
                </div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Assessed</dt><dd class="num">Rs. {{ number_format((float) $arrears['total_due'], 2) }}</dd>
                        <dt>Paid</dt><dd class="num">Rs. {{ number_format((float) $arrears['total_paid'], 2) }}</dd>
                        <dt>Remitted</dt><dd class="num">Rs. {{ number_format((float) $arrears['total_remitted'], 2) }}</dd>
                        <dt>Balance</dt>
                        <dd class="num"><strong>Rs. {{ number_format((float) $arrears['balance'], 2) }}</strong></dd>
                    </dl>
                    <div class="alert {{ $clearance['satisfied'] ? 'alert-good' : 'alert-warn' }}" style="margin:.9rem 0 0">
                        @include('partials.icon', ['name' => $clearance['satisfied'] ? 'check' : 'alert'])
                        <div><p class="mb-0">{{ $clearance['reason'] }}</p></div>
                    </div>
                </div>
            </div>

            {{-- ---------- Evidence ---------- --}}
            <div class="card">
                    <div class="card-head">
                        <h3>Evidence checklist</h3>
                    <span class="badge badge-neutral">{{ $a->documents->count() }}</span>
                </div>
                <div class="card-body tight">
                    @php
                        $uploaded = $a->documents->keyBy('document_type_id');
                    @endphp
                    <table class="data" style="font-size:.82rem">
                        <tbody>
                        @foreach ($docTypes as $dt)
                            @php $doc = $uploaded->get($dt->id); @endphp
                            <tr>
                                <td>
                                    {{ $dt->name }}
                                    @if ($dt->is_mandatory)<span class="req" title="Mandatory">*</span>@endif
                                    @if ($dt->is_certified_copy_required)
                                        <div class="faint" style="font-size:.72rem">Certified copy required</div>
                                    @endif
                                </td>
                                <td class="text-end nowrap">
                                    @if (! $doc)
                                        <span class="badge badge-neutral">Not uploaded</span>
                                    @elseif ($doc->status === 'VERIFIED')
                                        <span class="badge badge-good">Verified</span>
                                    @elseif ($doc->status === 'DEFICIENT')
                                        <span class="badge badge-danger">Deficient</span>
                                    @elseif ($doc->status === 'WAIVED')
                                        <span class="badge badge-gold">Waived</span>
                                    @else
                                        <span class="badge badge-info">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ---------- Litigation ---------- --}}
            @if ($a->litigations->isNotEmpty())
                <div class="card">
                    <div class="card-head"><h3>Litigation</h3></div>
                    <div class="card-body">
                        @foreach ($a->litigations as $l)
                            <dl class="kv">
                                <dt>Court</dt><dd>{{ $l->court_name }}</dd>
                                <dt>Case no.</dt><dd>{{ $l->case_no }}</dd>
                                <dt>Status</dt>
                                <dd>
                                    @if ($l->is_pending)<span class="badge badge-warn">Pending</span>@endif
                                    @if ($l->has_restraining_order)<span class="badge badge-danger">Restraining order</span>@endif
                                    @if ($l->is_direction_case)<span class="badge badge-info">Direction case</span>@endif
                                </dd>
                            </dl>
                            @if (! $loop->last)<hr class="divider">@endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ---------- History ---------- --}}
            <div class="card">
                    <div class="card-head"><h3>Case timeline</h3></div>
                <div class="card-body">
                    <ul class="timeline">
                        @foreach ($a->history as $h)
                            <li class="{{ $loop->first ? 'is-current' : 'is-done' }}">
                                <div class="t-title">
                                    {{ \App\Services\WorkflowService::LABELS[$h->to_status] ?? $h->to_status }}
                                </div>
                                <div class="t-meta">
                                    {{ \Illuminate\Support\Carbon::parse($h->occurred_at)->format('d-m-Y H:i') }}
                                    @if ($h->actor_role) &middot; {{ str_replace('_', ' ', $h->actor_role) }} @endif
                                </div>
                                @if ($h->remarks)
                                    <div class="t-meta">{{ $h->remarks }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

@endsection
