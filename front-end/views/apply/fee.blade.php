@extends('layouts.app')

@section('title', __('The deposit'))
@section('heading', __('Step 6 of 6 — The Rs. 5,000 deposit'))

@section('content')

@php
    $recorded = $application->feePayments->isNotEmpty();
    $confirmed = $application->payment_status === 'PAID';
@endphp

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>{{ __('The Rs. :amount deposit', ['amount' => number_format((float) $feeAmount, 0)]) }}</h1>
        <p class="lede">
            {{ __('The last step. Your application is not processed until this deposit is confirmed with the bank.') }}
        </p>
    </div>

    <div class="alert {{ $confirmed ? 'alert-good' : 'alert-warn' }}">
        @include('partials.icon', ['name' => $confirmed ? 'check' : 'alert'])
        <div>
            <p class="mb-0">
                {{ __('Payment status:') }} <strong>{{ __($application->payment_status ?? '') }}</strong>.
                @if ($confirmed)
                    {{ __('Accounts has confirmed your deposit and the department can now process your application.') }}
                @else
                    {!! __('Take a <strong>pay order, banker’s cheque or demand draft</strong> for Rs. :amount drawn in favour of <strong>Chairman ETPB</strong> to your district office, then record its details below. It becomes <strong>PAID</strong> once Accounts confirms it with the bank.', ['amount' => e(number_format((float) $feeAmount, 0))]) !!}
                @endif
            </p>
        </div>
    </div>

    @if ($recorded)
        <div class="card">
            <div class="card-head"><h3>{{ __('What you have recorded') }}</h3></div>
            <div class="card-body">
                @foreach ($application->feePayments as $p)
                    <dl class="kv">
                        <dt>{{ __('Instrument') }}</dt>
                        <dd>{{ __(ucwords(strtolower(str_replace('_', ' ', $p->instrument_type)))) }}
                            {{ $p->instrument_no }}</dd>
                        <dt>{{ __('Amount') }}</dt><dd>Rs. {{ number_format((float) $p->amount, 2) }}</dd>
                        <dt>{{ __('Bank') }}</dt>
                        <dd>{{ $p->bank_name }}, {{ $p->branch_name }}
                            @if ($p->branch_code) {{ __('(code :code)', ['code' => $p->branch_code]) }} @endif</dd>
                        <dt>{{ __('Status') }}</dt>
                        <dd>
                            <span class="badge badge-{{ $p->status === 'VERIFIED' ? 'good' : 'warn' }}">
                                {{ $p->status === 'VERIFIED' ? __('Confirmed by Accounts') : __(ucfirst(strtolower($p->status))) }}
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
            <div class="card-head"><h3>{{ __('Record your deposit') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('fee.store', $application) }}" novalidate>
                    @csrf

                    <div class="grid-2">
                        <div class="field">
                            <label for="instrument_type">{{ __('What did you use?') }} <span class="req">*</span></label>
                            <select id="instrument_type" name="instrument_type" class="select">
                                <option value="PAY_ORDER">{{ __('Pay order') }}</option>
                                <option value="BANKERS_CHEQUE">{{ __('Banker’s cheque') }}</option>
                                <option value="DEMAND_DRAFT">{{ __('Demand draft') }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="instrument_no">{{ __('Its number') }} <span class="req">*</span></label>
                            <input type="text" id="instrument_no" name="instrument_no" class="input"
                                   value="{{ old('instrument_no') }}" required maxlength="60">
                        </div>
                        <div class="field">
                            <label for="instrument_date">{{ __('Date on it') }} <span class="req">*</span></label>
                            <input type="date" id="instrument_date" name="instrument_date" class="input"
                                   value="{{ old('instrument_date') }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="field">
                            <label for="amount">{{ __('Amount (Rs.)') }} <span class="req">*</span></label>
                            <input type="text" id="amount" name="amount" class="input" inputmode="decimal"
                                   value="{{ old('amount', $feeAmount) }}" required>
                        </div>
                    </div>

                    <fieldset class="group">
                        <legend>{{ __('The bank') }}</legend>
                        <div class="field">
                            <label for="bank_name">{{ __('Bank') }} <span class="req">*</span></label>
                            <input type="text" id="bank_name" name="bank_name" class="input"
                                   value="{{ old('bank_name') }}" required maxlength="150">
                        </div>
                        <div class="grid-3">
                            <div class="field">
                                <label for="branch_name">{{ __('Branch') }} <span class="req">*</span></label>
                                <input type="text" id="branch_name" name="branch_name" class="input"
                                       value="{{ old('branch_name') }}" required maxlength="150">
                            </div>
                            <div class="field">
                                <label for="branch_code">{{ __('Branch code') }}</label>
                                <input type="text" id="branch_code" name="branch_code" class="input"
                                       value="{{ old('branch_code') }}" maxlength="30">
                            </div>
                            <div class="field">
                                <label for="district_id">{{ __('District') }}</label>
                                <select id="district_id" name="district_id" class="select">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}"
                                            @selected((int) old('district_id', $application->district_id) === (int) $d->id)>
                                            {{ __($d->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="group">
                        <legend>{{ __('Who deposited it') }}</legend>
                        <div class="grid-3">
                            <div class="field">
                                <label for="depositor_name">{{ __('Name') }} <span class="req">*</span></label>
                                <input type="text" id="depositor_name" name="depositor_name" class="input"
                                       value="{{ old('depositor_name', $application->applicant?->full_name) }}"
                                       required maxlength="150">
                            </div>
                            <div class="field">
                                <label for="depositor_cnic">{{ __('CNIC') }} <span class="req">*</span></label>
                                <input type="text" id="depositor_cnic" name="depositor_cnic" class="input"
                                       value="{{ old('depositor_cnic', $application->applicant?->cnic) }}"
                                       inputmode="numeric" pattern="[0-9]{13}" maxlength="13" required>
                            </div>
                            <div class="field">
                                <label for="depositor_contact">{{ __('Contact') }} <span class="req">*</span></label>
                                <input type="text" id="depositor_contact" name="depositor_contact" class="input"
                                       value="{{ old('depositor_contact', $application->applicant?->contact) }}"
                                       required maxlength="20">
                            </div>
                        </div>
                        <div class="field">
                            <label for="submission_date">{{ __('Date you deposited it') }} <span class="req">*</span></label>
                            <input type="date" id="submission_date" name="submission_date" class="input"
                                   value="{{ old('submission_date', now()->toDateString()) }}"
                                   max="{{ now()->toDateString() }}" required>
                        </div>
                    </fieldset>

                    <button class="btn btn-primary" type="submit">
                        @include('partials.icon', ['name' => 'cash']) {{ __('Record my deposit') }}
                    </button>
                </form>
            </div>
        </div>
    @endunless

    <div class="card">
        <div class="card-head">
            <h3>{{ __('Biometric verification') }} <span class="req">*</span></h3>
            <div class="card-actions"><span class="badge badge-{{ $hasBiometric ? 'good' : 'warn' }}">
                {{ $hasBiometric ? __('Captured') : __('Required') }}
            </span></div>
        </div>
        <div class="card-body">
            <p class="muted text-[.9rem]">
                {{ __('Biometric verification is mandatory. Upload a clear photo of your thumb impression (or a scan from the office biometric device).') }}
            </p>
            @if ($hasBiometric)
                <div class="alert alert-good">
                    @include('partials.icon', ['name' => 'check'])
                    <div><p class="mb-0">{{ __('Thumb impression is on file for this applicant.') }}</p></div>
                </div>
            @endif
            <form method="POST" action="{{ route('apply.biometric.store', $application) }}"
                  enctype="multipart/form-data" class="mt-2">
                @csrf
                <div class="field">
                    <label for="thumb">{{ __('Thumb impression image') }} <span class="req">*</span></label>
                    @include('partials.file-dropzone', [
                        'name' => 'thumb',
                        'id' => 'thumb',
                        'required' => true,
                        'accept' => '.jpg,.jpeg,.png,.webp',
                        'title' => __('Drop thumb impression photo here or click to browse'),
                        'subtitle' => __('JPEG, PNG or WebP from phone or biometric device'),
                        'hint' => __('A clear photo of the thumb impression is required before submit.'),
                    ])
                </div>
                <button type="submit" class="btn btn-outline mt-2">
                    @include('partials.icon', ['name' => 'check'])
                    {{ $hasBiometric ? __('Replace biometric') : __('Save biometric') }}
                </button>
            </form>
        </div>
    </div>

    <div class="alert alert-info">
        @include('partials.icon', ['name' => 'info'])
        <div><p class="mb-0">{{ __($paymentApiNote ?? '') }}</p></div>
    </div>

    {{-- ---------- Submit ---------- --}}
    <div class="card">
        <div class="card-head"><h3>{{ __('Submit your application') }}</h3></div>
        <div class="card-body">
            @unless ($hasBiometric)
                <div class="alert alert-danger">
                    @include('partials.icon', ['name' => 'alert'])
                    <div><p class="mb-0">{{ __('Capture biometric verification above before you can submit.') }}</p></div>
                </div>
            @endunless
            @if ($readiness['allowed'])
                <div class="alert alert-good">
                    @include('partials.icon', ['name' => 'check'])
                    <div><p class="mb-0">{{ __('You can submit now. Your application will be stored and sent for processing.') }}</p></div>
                </div>
                @if (! empty($missingDocs))
                    <div class="alert alert-warn mt-3">
                        @include('partials.icon', ['name' => 'alert'])
                        <div>
                            <strong>{{ __('Recommended evidence not yet attached') }}</strong>
                            <span class="faint">{{ __('(you may still submit; the District Officer can ask for these later):') }}</span>
                            <ul class="mb-0">
                                @foreach ($missingDocs as $docName)
                                    <li>{{ __($docName ?? '') }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @else
                <div class="alert alert-warn">
                    @include('partials.icon', ['name' => 'alert'])
                    <div>
                        <strong>{{ __('Before you can submit:') }}</strong>
                        <ul>
                            @foreach ($readiness['reasons'] as $reason)
                                <li>{{ __($reason ?? '') }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('apply.submit', $application) }}">
                @csrf
                <div class="wizard-actions mt-0 pt-0 border-0">
                    <a href="{{ route('apply.occupants', $application) }}" class="btn btn-ghost">{{ __('Back') }}</a>
                    <a href="{{ route('apply.evidence', $application) }}" class="btn btn-outline">
                        {{ __('Add more documents') }}
                    </a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn-primary btn-lg"
                            @disabled(! $readiness['allowed'] || ! $hasBiometric)>
                        @include('partials.icon', ['name' => 'check']) {{ __('Submit my application') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
