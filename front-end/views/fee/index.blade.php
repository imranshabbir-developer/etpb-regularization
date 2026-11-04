@extends('layouts.app')

@section('title', __('Processing fee'))
@section('heading', __('Processing fee — :no', ['no' => $application->application_no]))

@section('content')

@php $paid = $application->payment_status === 'PAID'; @endphp

<div class="page-head">
    <h1>{{ __('Rs. :amount processing fee', ['amount' => number_format((float) $feeAmount, 0)]) }}</h1>
    <p class="lede">
        {!! __('The applicant deposits the fee in favour of :payee. Until Accounts confirms the instrument with the bank, the application stays <strong>pending</strong> and the department does not process it.', ['payee' => '<strong>'.e($payee).'</strong>']) !!}
    </p>
    <div class="inline-list mt-1">
        <a href="{{ route('applications.show', $application) }}" class="badge badge-neutral">&larr; {{ __('Case file') }}</a>
        <span class="badge badge-{{ $paid ? 'good' : 'warn' }}">
            {{ $paid ? __('Payment PAID') : __('Payment PENDING') }}
        </span>
        <span class="badge badge-{{ $application->statusTone() }}">{{ __($application->statusLabel()) }}</span>
    </div>
</div>

<div class="alert {{ $paid ? 'alert-good' : 'alert-warn' }}">
    @include('partials.icon', ['name' => $paid ? 'check' : 'alert'])
    <div>
        @if ($paid)
            <p class="mb-0">
                @if ($application->payment_confirmed_at)
                    {{ __('Payment confirmed on :date.', ['date' => \Illuminate\Support\Carbon::parse($application->payment_confirmed_at)->translatedFormat('d-m-Y')]) }}
                @else
                    {{ __('Payment confirmed.') }}
                @endif
                {{ __('The application may now be processed.') }}
            </p>
        @else
            <p class="mb-0">
                <strong>{{ __('This application will not be processed.') }}</strong>
                {{ __('Every departmental step — scrutiny, site inspection, rent assessment, notices, approval — is refused until the deposit is confirmed.') }}
            </p>
        @endif
    </div>
</div>

<div class="grid-2" style="gap:1.15rem;align-items:start">
    <div>
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Instruments on record') }}</h3>
                <span class="badge badge-neutral">{{ $payments->count() }}</span>
            </div>

            @if ($payments->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'cash'])
                    <p class="mb-0">{{ __('No deposit recorded.') }}</p>
                    <p class="hint">{{ __('The application cannot be submitted or processed without it.') }}</p>
                </div>
            @else
                <div class="card-body">
                    @foreach ($payments as $p)
                        <div class="inline-list">
                            <strong>{{ __(ucwords(strtolower(str_replace('_', ' ', $p->instrument_type)))) }}
                                {{ $p->instrument_no }}</strong>
                            <span class="badge badge-{{ $p->status === 'VERIFIED' ? 'good' : ($p->status === 'PENDING' ? 'warn' : 'danger') }}">
                                {{ $p->status === 'VERIFIED' ? __('Confirmed') : __(ucfirst(strtolower($p->status))) }}
                            </span>
                        </div>

                        <dl class="kv mt-1">
                            <dt>{{ __('Amount') }}</dt>
                            <dd><strong>Rs. {{ number_format((float) $p->amount, 2) }}</strong></dd>
                            <dt>{{ __('In favour of') }}</dt><dd>{{ $p->payee }}</dd>
                            <dt>{{ __('Instrument date') }}</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($p->instrument_date)->translatedFormat('d-m-Y') }}</dd>
                            <dt>{{ __('Date of submission') }}</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($p->submission_date)->translatedFormat('d-m-Y') }}</dd>
                            <dt>{{ __('Bank') }}</dt>
                            <dd>
                                {{ $p->bank_name }}
                                <div class="faint" style="font-size:.8rem">
                                    {{ $p->branch_name }}@if ($p->branch_code) &middot; {{ __('code :code', ['code' => $p->branch_code]) }}@endif
                                </div>
                            </dd>
                            <dt>{{ __('Depositor') }}</dt>
                            <dd>
                                {{ $p->depositor_name }}
                                <div class="faint" style="font-size:.8rem">
                                    {{ $p->depositor_cnic }} &middot; {{ $p->depositor_contact }}
                                </div>
                            </dd>
                            @if ($p->bank_confirmation_ref)
                                <dt>{{ __('Bank confirmation') }}</dt><dd>{{ $p->bank_confirmation_ref }}</dd>
                            @endif
                        </dl>

                        @if ($p->verification_remarks)
                            <p class="muted" style="font-size:.85rem;white-space:pre-wrap">{{ $p->verification_remarks }}</p>
                        @endif

                        @if ($p->status === 'PENDING')
                            @can('do', 'fee.verify')
                                <form method="POST" action="{{ route('fee.confirm', $p) }}" class="mt-2">
                                    @csrf
                                    <fieldset class="group">
                                        <legend>{{ __('Accounts confirmation') }}</legend>
                                        <div class="grid-2">
                                            <div class="field">
                                                <label for="dec_{{ $p->id }}">{{ __('Decision') }}</label>
                                                <select id="dec_{{ $p->id }}" name="decision" class="select">
                                                    <option value="VERIFIED">{{ __('Confirmed with the bank') }}</option>
                                                    <option value="BOUNCED">{{ __('Bounced') }}</option>
                                                    <option value="REJECTED">{{ __('Rejected') }}</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="ref_{{ $p->id }}">{{ __('Bank confirmation reference') }}</label>
                                                <input type="text" id="ref_{{ $p->id }}" name="bank_confirmation_ref" class="input">
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label for="vr_{{ $p->id }}">{{ __('Remarks') }} <span class="req">*</span></label>
                                            <textarea id="vr_{{ $p->id }}" name="verification_remarks" class="textarea"
                                                      style="min-height:70px" required minlength="10"
                                                      placeholder="{{ __('How the instrument was confirmed, or why it was refused.') }}"></textarea>
                                        </div>
                                        <button class="btn btn-primary btn-sm" type="submit">{{ __('Record decision') }}</button>
                                    </fieldset>
                                </form>
                            @else
                                <p class="hint">{{ __('Awaiting confirmation by Accounts.') }}</p>
                            @endcan
                        @endif

                        @if (! $loop->last)<hr class="divider">@endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div>
        @can('do', 'fee.record')
            <div class="card">
                <div class="card-head"><h3>{{ __('Record a deposit') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('fee.store', $application) }}">
                        @csrf

                        <div class="field">
                            <label for="instrument_type">{{ __('Instrument') }} <span class="req">*</span></label>
                            <select id="instrument_type" name="instrument_type" class="select">
                                <option value="PAY_ORDER">{{ __('Pay order') }}</option>
                                <option value="BANKERS_CHEQUE">{{ __("Banker's cheque") }}</option>
                                <option value="DEMAND_DRAFT">{{ __('Demand draft') }}</option>
                            </select>
                            <p class="hint">{!! __('Drawn in favour of :payee.', ['payee' => '<strong>'.e($payee).'</strong>']) !!}</p>
                        </div>

                        <div class="grid-2">
                            <div class="field">
                                <label for="instrument_no">{{ __('Instrument no.') }} <span class="req">*</span></label>
                                <input type="text" id="instrument_no" name="instrument_no" class="input"
                                       value="{{ old('instrument_no') }}" required maxlength="60">
                            </div>
                            <div class="field">
                                <label for="instrument_date">{{ __('Instrument date') }} <span class="req">*</span></label>
                                <input type="date" id="instrument_date" name="instrument_date" class="input"
                                       value="{{ old('instrument_date') }}" max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="amount">{{ __('Amount (Rs.)') }} <span class="req">*</span></label>
                                <input type="text" id="amount" name="amount" class="input" inputmode="decimal"
                                       value="{{ old('amount', $feeAmount) }}" required>
                            </div>
                            <div class="field">
                                <label for="submission_date">{{ __('Date of submission') }} <span class="req">*</span></label>
                                <input type="date" id="submission_date" name="submission_date" class="input"
                                       value="{{ old('submission_date', now()->toDateString()) }}"
                                       max="{{ now()->toDateString() }}" required>
                            </div>
                        </div>

                        <fieldset class="group">
                            <legend>{{ __('Bank') }}</legend>
                            <div class="field">
                                <label for="bank_name">{{ __('Bank') }} <span class="req">*</span></label>
                                <input type="text" id="bank_name" name="bank_name" class="input"
                                       value="{{ old('bank_name') }}" required maxlength="150">
                            </div>
                            <div class="grid-2">
                                <div class="field">
                                    <label for="branch_name">{{ __('Branch / location') }} <span class="req">*</span></label>
                                    <input type="text" id="branch_name" name="branch_name" class="input"
                                           value="{{ old('branch_name') }}" required maxlength="150">
                                </div>
                                <div class="field">
                                    <label for="branch_code">{{ __('Branch code') }}</label>
                                    <input type="text" id="branch_code" name="branch_code" class="input"
                                           value="{{ old('branch_code') }}" maxlength="30">
                                </div>
                            </div>
                            <div class="field">
                                <label for="district_id">{{ __('District') }}</label>
                                <select id="district_id" name="district_id" class="select">
                                    <option value="">{{ __('Select a district') }}</option>
                                    @foreach ($districts as $d)
                                        <option value="{{ $d->id }}"
                                            @selected((int) old('district_id', $application->district_id) === (int) $d->id)>
                                            {{ __($d->name ?? '') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </fieldset>

                        <fieldset class="group">
                            <legend>{{ __('Depositor') }}</legend>
                            <div class="field">
                                <label for="depositor_name">{{ __('Name') }} <span class="req">*</span></label>
                                <input type="text" id="depositor_name" name="depositor_name" class="input"
                                       value="{{ old('depositor_name', $application->applicant?->full_name) }}"
                                       required maxlength="150">
                            </div>
                            <div class="grid-2">
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
                        </fieldset>

                        <button class="btn btn-primary" type="submit" style="width:100%">
                            @include('partials.icon', ['name' => 'cash']) {{ __('Record deposit') }}
                        </button>
                        <p class="hint mt-1">
                            {!! __('Recording is not payment. The status changes to <strong>paid</strong> only when Accounts confirms the instrument with the bank.') !!}
                        </p>
                    </form>
                </div>
            </div>
        @endcan
    </div>
</div>

@endsection
