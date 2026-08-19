@extends('layouts.app')

@section('title', 'The deposit')
@section('heading', 'Step 6 of 6 — The Rs. 5,000 deposit')

@section('content')

@php
    $recorded = $application->feePayments->isNotEmpty();
    $confirmed = $application->payment_status === 'PAID';
@endphp

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>The Rs. {{ number_format((float) $feeAmount, 0) }} deposit</h1>
        <p class="lede">
            The last step. Your application is not processed until this deposit is confirmed
            with the bank.
        </p>
    </div>

    <div class="alert {{ $confirmed ? 'alert-good' : 'alert-warn' }}">
        @include('partials.icon', ['name' => $confirmed ? 'check' : 'alert'])
        <div>
            <p class="mb-0">
                Payment status: <strong>{{ $application->payment_status }}</strong>.
                @if ($confirmed)
                    Accounts has confirmed your deposit and the department can now process your application.
                @else
                    Take a <strong>pay order, banker&rsquo;s cheque or demand draft</strong> for
                    Rs. {{ number_format((float) $feeAmount, 0) }} drawn in favour of
                    <strong>Chairman ETPB</strong> to your district office, then record its details
                    below. It becomes <strong>PAID</strong> once Accounts confirms it with the bank.
                @endif
            </p>
        </div>
    </div>

    @if ($recorded)
        <div class="card">
            <div class="card-head"><h3>What you have recorded</h3></div>
            <div class="card-body">
                @foreach ($application->feePayments as $p)
                    <dl class="kv">
                        <dt>Instrument</dt>
                        <dd>{{ ucwords(strtolower(str_replace('_', ' ', $p->instrument_type))) }}
                            {{ $p->instrument_no }}</dd>
                        <dt>Amount</dt><dd>Rs. {{ number_format((float) $p->amount, 2) }}</dd>
                        <dt>Bank</dt>
                        <dd>{{ $p->bank_name }}, {{ $p->branch_name }}
                            @if ($p->branch_code) (code {{ $p->branch_code }}) @endif</dd>
                        <dt>Status</dt>
                        <dd>
                            <span class="badge badge-{{ $p->status === 'VERIFIED' ? 'good' : 'warn' }}">
                                {{ $p->status === 'VERIFIED' ? 'Confirmed by Accounts' : ucfirst(strtolower($p->status)) }}
                            </span>
                        </dd>
                    </dl>
                    @if (! $loop->last)<hr class="divider">@endif
                @endforeach
            </div>
        </div>
    @endif

    @unless ($confirmed)
        <div class="card">
            <div class="card-head"><h3>Record your deposit</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fee.store', $application) }}" novalidate>
                    @csrf

                    <div class="grid-2">
                        <div class="field">
                            <label for="instrument_type">What did you use? <span class="req">*</span></label>
                            <select id="instrument_type" name="instrument_type" class="select">
                                <option value="PAY_ORDER">Pay order</option>
                                <option value="BANKERS_CHEQUE">Banker&rsquo;s cheque</option>
                                <option value="DEMAND_DRAFT">Demand draft</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="instrument_no">Its number <span class="req">*</span></label>
                            <input type="text" id="instrument_no" name="instrument_no" class="input"
                                   value="{{ old('instrument_no') }}" required maxlength="60">
                        </div>
                        <div class="field">
                            <label for="instrument_date">Date on it <span class="req">*</span></label>
                            <input type="date" id="instrument_date" name="instrument_date" class="input"
                                   value="{{ old('instrument_date') }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="amount">Amount (Rs.) <span class="req">*</span></label>
                            <input type="text" id="amount" name="amount" class="input" inputmode="decimal"
                                   value="{{ old('amount', $feeAmount) }}" required>
                        </div>
                    </div>

                    <fieldset class="group">
                        <legend>The bank</legend>
                        <div class="field">
                            <label for="bank_name">Bank <span class="req">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" class="input"
                                   value="{{ old('bank_name') }}" required maxlength="150">
                        </div>
                        <div class="grid-3">
                            <div class="field">
                                <label for="branch_name">Branch <span class="req">*</span></label>
                                <input type="text" id="branch_name" name="branch_name" class="input"
                                       value="{{ old('branch_name') }}" required maxlength="150">
                            </div>
                            <div class="field">
                                <label for="branch_code">Branch code</label>
                                <input type="text" id="branch_code" name="branch_code" class="input"
                                       value="{{ old('branch_code') }}" maxlength="30">
                            </div>
                            <div class="field">
                                <label for="district_id">District</label>
                                <select id="district_id" name="district_id" class="select">
                                    <option value="">Select</option>
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}"
                                            @selected((int) old('district_id', $application->district_id) === (int) $d->id)>
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="group">
                        <legend>Who deposited it</legend>
                        <div class="grid-3">
                            <div class="field">
                                <label for="depositor_name">Name <span class="req">*</span></label>
                                <input type="text" id="depositor_name" name="depositor_name" class="input"
                                       value="{{ old('depositor_name', $application->applicant?->full_name) }}"
                                       required maxlength="150">
                            </div>
                            <div class="field">
                                <label for="depositor_cnic">CNIC <span class="req">*</span></label>
                                <input type="text" id="depositor_cnic" name="depositor_cnic" class="input"
                                       value="{{ old('depositor_cnic', $application->applicant?->cnic) }}"
                                       inputmode="numeric" pattern="[0-9]{13}" maxlength="13" required>
                            </div>
                            <div class="field">
                                <label for="depositor_contact">Contact <span class="req">*</span></label>
                                <input type="text" id="depositor_contact" name="depositor_contact" class="input"
                                       value="{{ old('depositor_contact', $application->applicant?->contact) }}"
                                       required maxlength="20">
                            </div>
                        </div>
                        <div class="field">
                            <label for="submission_date">Date you deposited it <span class="req">*</span></label>
                            <input type="date" id="submission_date" name="submission_date" class="input"
                                   value="{{ old('submission_date', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}" required>
                        </div>
                    </fieldset>

                    <button class="btn btn-primary" type="submit">
                        @include('partials.icon', ['name' => 'cash']) Record my deposit
                    </button>
                </form>
            </div>
        </div>
    @endunless

    {{-- ---------- Submit ---------- --}}
    <div class="card">
        <div class="card-head"><h3>Submit your application</h3></div>
        <div class="card-body">
            @if ($readiness['allowed'])
                <div class="alert alert-good">
                    @include('partials.icon', ['name' => 'check'])
                    <div><p class="mb-0">Everything needed is on file. You can submit now.</p></div>
                </div>
            @else
                <div class="alert alert-warn">
                    @include('partials.icon', ['name' => 'alert'])
                    <div>
                        <strong>Before you can submit:</strong>
                        <ul>
                            @foreach ($readiness['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('apply.submit', $application) }}">
                @csrf
                <div class="wizard-actions mt-0 pt-0 border-0">
                    <a href="{{ route('apply.occupants', $application) }}" class="btn btn-ghost">Back</a>
                    <a href="{{ route('apply.evidence', $application) }}" class="btn btn-outline">
                        Add more documents
                    </a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn-primary btn-lg"
                            @disabled(! $readiness['allowed'])>
                        @include('partials.icon', ['name' => 'check']) Submit my application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
