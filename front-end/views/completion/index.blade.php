@extends('layouts.app')

@section('title', 'Completion')
@section('heading', 'Completion — ' . $application->application_no)

@section('content')

@php
    $a = $application;
    $fmt = fn ($d, $f = 'd-m-Y') => $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
    $rs  = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
@endphp

<div class="page-head">
    <h1>Completion</h1>
    <p class="lede">
        The nomination form, the tenancy agreement, and the regularization order &mdash;
        the three acts that turn an approved application into a recorded tenancy.
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $a) }}" class="badge badge-neutral">&larr; Case file</a>
        <span class="badge badge-{{ $a->statusTone() }}">{{ $a->statusLabel() }}</span>
        <span class="clause">Clause 3(ii)(b)</span>
        <span class="clause">Para 3(iii)(B)</span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <ul class="timeline">
            <li class="{{ $a->approvals->where('level','ADMINISTRATOR')->where('action','APPROVE')->isNotEmpty() ? 'is-done' : '' }}">
                <div class="t-title">Approved by the Administrator</div>
                <div class="t-meta">Clause 3(ii)(d)</div>
            </li>
            <li class="{{ $nominee ? 'is-done' : 'is-current' }}">
                <div class="t-title">Nomination form obtained</div>
                <div class="t-meta">
                    Para 3(iii)(B) &mdash; the possession cannot be regularized without it
                </div>
            </li>
            <li class="{{ $agreement ? 'is-done' : ($nominee ? 'is-current' : '') }}">
                <div class="t-title">Tenancy agreement executed</div>
                <div class="t-meta">Clause 3(ii)(b)</div>
            </li>
            <li class="{{ $order ? 'is-done' : ($agreement ? 'is-current' : '') }}">
                <div class="t-title">Regularization order issued</div>
                <div class="t-meta">Case closed</div>
            </li>
        </ul>
    </div>
</div>

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        {{-- ---------- Nominee ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Nomination form</h3>
                <div class="card-actions"><span class="clause">Para 3(iii)(B)</span></div>
            </div>
            <div class="card-body">
                @if ($nominee)
                    <dl class="kv">
                        <dt>Nominee</dt><dd>{{ $nominee->nominee_name }}</dd>
                        @if ($nominee->nominee_parentage)
                            <dt>Parentage</dt><dd>{{ $nominee->nominee_parentage }}</dd>
                        @endif
                        <dt>Relationship</dt><dd>{{ $nominee->relationship }}</dd>
                        <dt>CNIC</dt><dd class="num">{{ $nominee->nominee_cnic ?: '—' }}</dd>
                        <dt>Contact</dt><dd>{{ $nominee->nominee_contact ?: '—' }}</dd>
                        <dt>Form received</dt><dd>{{ $fmt($nominee->form_received_on) }}</dd>
                    </dl>

                    @if ($nominee->heirs->isNotEmpty())
                        <h4 class="mt-2">Legal heirs</h4>
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>#</th><th>Name</th><th>Relationship</th><th>CNIC</th></tr></thead>
                                <tbody>
                                @foreach ($nominee->heirs as $h)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $h->heir_name }}</td>
                                        <td>{{ $h->relationship }}</td>
                                        <td class="num">{{ $h->cnic ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warn">
                        @include('partials.icon', ['name' => 'alert'])
                        <div>
                            <p class="mb-0">
                                Not yet obtained. Under the proviso to Scheme para 3(iii)(B) the District
                                Officer <strong>shall not</strong> regularize the possession until this
                                form is on record.
                            </p>
                        </div>
                    </div>

                    @can('do', 'nominees.manage')
                        <form method="POST" action="{{ route('completion.nominee.store', $a) }}">
                            @csrf
                            <div class="grid-2">
                                <div class="field">
                                    <label for="nominee_name">Nominee <span class="req">*</span></label>
                                    <input type="text" id="nominee_name" name="nominee_name" class="input" required>
                                </div>
                                <div class="field">
                                    <label for="relationship">Relationship <span class="req">*</span></label>
                                    <input type="text" id="relationship" name="relationship" class="input"
                                           required placeholder="Son, daughter, wife, brother">
                                </div>
                                <div class="field">
                                    <label for="nominee_parentage">Parentage</label>
                                    <input type="text" id="nominee_parentage" name="nominee_parentage" class="input">
                                </div>
                                <div class="field">
                                    <label for="nominee_cnic">CNIC</label>
                                    <input type="text" id="nominee_cnic" name="nominee_cnic" class="input"
                                           inputmode="numeric" maxlength="13" pattern="[0-9]{13}">
                                </div>
                                <div class="field">
                                    <label for="nominee_contact">Contact</label>
                                    <input type="text" id="nominee_contact" name="nominee_contact" class="input">
                                </div>
                                <div class="field">
                                    <label for="form_received_on">Form received on <span class="req">*</span></label>
                                    <input type="date" id="form_received_on" name="form_received_on" class="input"
                                           value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                </div>
                            </div>

                            <fieldset class="group">
                                <legend>Legal heirs</legend>
                                @for ($i = 0; $i < 5; $i++)
                                    <div class="grid-3">
                                        <div class="field">
                                            <label for="h{{ $i }}n">Name</label>
                                            <input type="text" id="h{{ $i }}n" name="heirs[{{ $i }}][heir_name]" class="input">
                                        </div>
                                        <div class="field">
                                            <label for="h{{ $i }}r">Relationship</label>
                                            <input type="text" id="h{{ $i }}r" name="heirs[{{ $i }}][relationship]" class="input">
                                        </div>
                                        <div class="field">
                                            <label for="h{{ $i }}c">CNIC</label>
                                            <input type="text" id="h{{ $i }}c" name="heirs[{{ $i }}][cnic]" class="input"
                                                   inputmode="numeric" maxlength="13" pattern="[0-9]{13}">
                                        </div>
                                    </div>
                                @endfor
                            </fieldset>

                            <button class="btn btn-primary" type="submit">Record nomination form</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <div>
        {{-- ---------- Tenancy agreement ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Tenancy agreement</h3>
                <div class="card-actions"><span class="clause">Clause 3(ii)(b)</span></div>
            </div>
            <div class="card-body">
                @if ($agreement)
                    <dl class="kv">
                        <dt>Agreement no.</dt><dd>{{ $agreement->agreement_no }}</dd>
                        <dt>Executed on</dt><dd>{{ $fmt($agreement->executed_on) }}</dd>
                        <dt>Monthly rent</dt><dd><strong>{{ $rs($agreement->monthly_rent) }}</strong></dd>
                        <dt>Security</dt><dd>{{ $agreement->security_amount ? $rs($agreement->security_amount) : '—' }}</dd>
                        <dt>Effective from</dt><dd>{{ $fmt($agreement->effective_from) }}</dd>
                        <dt>Status</dt>
                        <dd><span class="badge badge-good">{{ ucfirst(strtolower($agreement->status)) }}</span></dd>
                    </dl>
                @elseif (! $nominee)
                    <p class="muted mb-0">The nomination form must be obtained first.</p>
                @else
                    @unless ($canExecute['allowed'])
                        <div class="alert alert-warn">
                            @include('partials.icon', ['name' => 'alert'])
                            <div>
                                <ul style="margin:0;padding-inline-start:1.05rem">
                                    @foreach ($canExecute['reasons'] as $r)<li>{{ $r }}</li>@endforeach
                                </ul>
                            </div>
                        </div>
                    @endunless

                    @can('do', 'agreements.execute')
                        <form method="POST" action="{{ route('completion.agreement.store', $a) }}">
                            @csrf
                            <div class="grid-2">
                                <div class="field">
                                    <label for="executed_on">Executed on <span class="req">*</span></label>
                                    <input type="date" id="executed_on" name="executed_on" class="input"
                                           value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="field">
                                    <label for="monthly_rent">Monthly rent (Rs.) <span class="req">*</span></label>
                                    <input type="text" id="monthly_rent" name="monthly_rent" class="input"
                                           inputmode="decimal" required
                                           value="{{ $a->assessed_monthly_rent }}">
                                </div>
                                <div class="field">
                                    <label for="security_amount">Security (Rs.)</label>
                                    <input type="text" id="security_amount" name="security_amount" class="input"
                                           inputmode="decimal">
                                </div>
                                <div class="field">
                                    <label for="effective_from">Effective from <span class="req">*</span></label>
                                    <input type="date" id="effective_from" name="effective_from" class="input"
                                           value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="field">
                                    <label for="stamp_paper_no">Stamp paper no.</label>
                                    <input type="text" id="stamp_paper_no" name="stamp_paper_no" class="input">
                                </div>
                                <div class="field">
                                    <label for="stamp_paper_value">Stamp paper value (Rs.)</label>
                                    <input type="text" id="stamp_paper_value" name="stamp_paper_value" class="input"
                                           inputmode="decimal">
                                </div>
                            </div>
                            <div class="field">
                                <label for="terms">Terms</label>
                                <textarea id="terms" name="terms" class="textarea" style="min-height:90px"></textarea>
                            </div>
                            <button class="btn btn-primary" type="submit" style="width:100%">
                                Execute agreement
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        {{-- ---------- Regularization order ---------- --}}
        <div class="card">
            <div class="card-head"><h3>Regularization order</h3></div>
            <div class="card-body">
                @if ($order)
                    <dl class="kv">
                        <dt>Order no.</dt><dd>{{ $order->order_no }}</dd>
                        <dt>Dated</dt><dd>{{ $fmt($order->order_date) }}</dd>
                        <dt>Area regularized</dt>
                        <dd>{{ $order->regularized_area_sqft ? number_format((float) $order->regularized_area_sqft, 2) . ' sqft' : '—' }}</dd>
                        <dt>Rent fixed</dt><dd>{{ $rs($order->monthly_rent_fixed) }}</dd>
                    </dl>
                    <hr class="divider">
                    <p class="muted" style="white-space:pre-wrap;font-size:.87rem">{{ $order->order_text }}</p>
                    <div class="btn-row mt-2">
                        <a href="{{ route('reports.deep', $a) }}" class="btn btn-outline btn-sm">Deep report</a>
                    </div>
                @elseif (! $agreement)
                    <p class="muted mb-0">The tenancy agreement must be executed first.</p>
                @else
                    @can('do', 'orders.issue')
                        <form method="POST" action="{{ route('completion.order.store', $a) }}">
                            @csrf
                            <div class="field">
                                <label for="order_date">Order date <span class="req">*</span></label>
                                <input type="date" id="order_date" name="order_date" class="input"
                                       value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="order_text">Order <span class="req">*</span></label>
                                <textarea id="order_text" name="order_text" class="textarea"
                                          style="min-height:160px" required minlength="60">{{ old('order_text', trim("
The possession of " . $a->applicant?->nameWithParentage() . " over " . $a->property?->identity() .
", " . $a->property?->address . ", measuring " .
($a->property?->currentArea ? number_format((float) $a->property->currentArea->area_sqft, 2) . ' square feet' : '') .
", is hereby regularized under Clause 3(ii) of the Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977. The occupant is treated as a tenant with effect from the date of the tenancy agreement, at the monthly rent fixed by the District Officer.
")) }}</textarea>
                            </div>
                            <button class="btn btn-primary btn-lg" type="submit" style="width:100%">
                                @include('partials.icon', ['name' => 'check']) Issue order and close the case
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
