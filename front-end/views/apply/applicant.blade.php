@extends('layouts.app')

@section('title', __('About you'))
@section('heading', __('Step 1 of 6 — About you'))

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>{{ __('About you') }}</h1>
        <p class="lede">{{ __('Just the particulars needed to open your file. Extra details can wait.') }}</p>
    </div>

    <form method="POST" action="{{ route('apply.applicant.store') }}" novalidate>
        @csrf

        <div class="essentials-banner">
            <span aria-hidden="true">★</span>
            <div>
                <strong>{{ __('Required:') }}</strong> {{ __('full name, parentage, CNIC, mobile, and postal address (about 5 fields). Email and living district are optional.') }}
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="field">
                    <label for="full_name">{{ __('Your full name') }} <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name"
                           class="input @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name', $draft['applicant']['full_name'] ?? $profile?->full_name ?? auth()->user()->name) }}"
                           required maxlength="150" autofocus>
                    @error('full_name')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="cnic">{{ __('CNIC') }} <span class="req">*</span></label>
                    <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                           value="{{ old('cnic', $draft['applicant']['cnic'] ?? $profile?->cnic ?? auth()->user()->cnic) }}"
                           required inputmode="numeric" pattern="[0-9]{13}" maxlength="13"
                           placeholder="3520112345671">
                    <p class="hint">{{ __('13 digits, without dashes.') }}</p>
                    @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="parentage_type">{{ __('You are the') }} <span class="req">*</span></label>
                        <select id="parentage_type" name="parentage_type" class="select">
                            <option value="FATHER" @selected(old('parentage_type', $draft['applicant']['parentage_type'] ?? $profile?->parentage_type ?? 'FATHER') === 'FATHER')>
                                {{ __('son or daughter of') }}
                            </option>
                            <option value="HUSBAND" @selected(old('parentage_type', $draft['applicant']['parentage_type'] ?? $profile?->parentage_type ?? '') === 'HUSBAND')>
                                {{ __('wife of') }}
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="parentage_name">{{ __('Their name') }} <span class="req">*</span></label>
                        <input type="text" id="parentage_name" name="parentage_name"
                               class="input @error('parentage_name') is-invalid @enderror"
                               value="{{ old('parentage_name', $draft['applicant']['parentage_name'] ?? $profile?->parentage_name ?? '') }}"
                               required maxlength="150">
                        @error('parentage_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="field">
                    <label for="contact">{{ __('Mobile number') }} <span class="req">*</span></label>
                    <input type="tel" id="contact" name="contact"
                           class="input @error('contact') is-invalid @enderror"
                           value="{{ old('contact', $draft['applicant']['contact'] ?? $profile?->contact ?? auth()->user()->contact) }}"
                           required maxlength="20" placeholder="0300-1234567">
                    <p class="hint">{{ __('The office will call this number if anything is unclear.') }}</p>
                    @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="postal_address">{{ __('Your postal address') }} <span class="req">*</span></label>
                    <textarea id="postal_address" name="postal_address"
                              class="textarea @error('postal_address') is-invalid @enderror"
                              required maxlength="500">{{ old('postal_address', $draft['applicant']['postal_address'] ?? $profile?->postal_address ?? '') }}</textarea>
                    @error('postal_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <details class="optional-panel">
            <summary>
                {{ __('Extra contact details') }}
                <span class="badge badge-neutral">{{ __('Optional') }}</span>
            </summary>
            <div class="optional-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="email">{{ __('Email') }}</label>
                        <input type="email" id="email" name="email" class="input"
                               value="{{ old('email', $draft['applicant']['email'] ?? $profile?->email ?? auth()->user()->email) }}"
                               maxlength="150">
                    </div>
                    <div class="field">
                        <label for="address_district_id">{{ __('District you live in') }}</label>
                        <select id="address_district_id" name="address_district_id" class="select">
                            <option value="">{{ __('Select a district') }}</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}"
                                    @selected((int) old('address_district_id', $draft['applicant']['address_district_id'] ?? $profile?->address_district_id ?? 0) === $d->id)>
                                    {{ __($d->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </details>

        <div class="wizard-actions">
            <a href="{{ route('apply.start') }}" class="btn btn-ghost">{{ __('Back') }}</a>
            <span class="spacer"></span>
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __('Continue') }} @include('partials.icon', ['name' => 'arrow-right'])
            </button>
        </div>
    </form>
</div>

@endsection
