@extends('layouts.app')

@section('title', 'About you')
@section('heading', 'Step 1 of 6 — About you')

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>About you</h1>
        <p class="lede">Your own particulars, as they appear on your CNIC.</p>
    </div>

    <form method="POST" action="{{ route('apply.applicant.store') }}" novalidate>
        @csrf

        <div class="card">
            <div class="card-body">
                <div class="field">
                    <label for="full_name">Your full name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name"
                           class="input @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name', $draft['applicant']['full_name'] ?? auth()->user()->name) }}"
                           required maxlength="150" autofocus>
                    @error('full_name')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="cnic">CNIC <span class="req">*</span></label>
                    <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                           value="{{ old('cnic', $draft['applicant']['cnic'] ?? auth()->user()->cnic) }}"
                           required inputmode="numeric" pattern="[0-9]{13}" maxlength="13"
                           placeholder="3520112345671">
                    <p class="hint">13 digits, without dashes.</p>
                    @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="parentage_type">You are the <span class="req">*</span></label>
                        <select id="parentage_type" name="parentage_type" class="select">
                            <option value="FATHER" @selected(old('parentage_type', $draft['applicant']['parentage_type'] ?? 'FATHER') === 'FATHER')>
                                son or daughter of
                            </option>
                            <option value="HUSBAND" @selected(old('parentage_type', $draft['applicant']['parentage_type'] ?? '') === 'HUSBAND')>
                                wife of
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="parentage_name">Their name <span class="req">*</span></label>
                        <input type="text" id="parentage_name" name="parentage_name"
                               class="input @error('parentage_name') is-invalid @enderror"
                               value="{{ old('parentage_name', $draft['applicant']['parentage_name'] ?? '') }}"
                               required maxlength="150">
                        @error('parentage_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="contact">Mobile number <span class="req">*</span></label>
                        <input type="tel" id="contact" name="contact"
                               class="input @error('contact') is-invalid @enderror"
                               value="{{ old('contact', $draft['applicant']['contact'] ?? auth()->user()->contact) }}"
                               required maxlength="20" placeholder="0300-1234567">
                        <p class="hint">We will use this to reach you about your application.</p>
                        @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="input"
                               value="{{ old('email', $draft['applicant']['email'] ?? auth()->user()->email) }}"
                               maxlength="150">
                    </div>
                </div>

                <div class="field">
                    <label for="postal_address">Your postal address <span class="req">*</span></label>
                    <textarea id="postal_address" name="postal_address"
                              class="textarea @error('postal_address') is-invalid @enderror"
                              required maxlength="500">{{ old('postal_address', $draft['applicant']['postal_address'] ?? '') }}</textarea>
                    <p class="hint">Where notices should be sent, if different from the property.</p>
                    @error('postal_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="address_district_id">District you live in</label>
                    <select id="address_district_id" name="address_district_id" class="select">
                        <option value="">Select a district</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}"
                                @selected((int) old('address_district_id', $draft['applicant']['address_district_id'] ?? 0) === $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <fieldset class="group">
                    <legend>Do any of these apply to you?</legend>
                    <p class="hint mt-0">
                        The Chairman may reduce or waive the rent for people who are indigent,
                        widowed or orphaned. Tick only what you can evidence.
                        <span class="clause">Clause 12</span>
                    </p>
                    <div class="grid-3">
                        <label class="font-medium">
                            <input type="checkbox" name="is_indigent" value="1"
                                   @checked(old('is_indigent', $draft['applicant']['is_indigent'] ?? false))> Indigent
                        </label>
                        <label class="font-medium">
                            <input type="checkbox" name="is_widow" value="1"
                                   @checked(old('is_widow', $draft['applicant']['is_widow'] ?? false))> Widow
                        </label>
                        <label class="font-medium">
                            <input type="checkbox" name="is_orphan" value="1"
                                   @checked(old('is_orphan', $draft['applicant']['is_orphan'] ?? false))> Orphan
                        </label>
                    </div>
                </fieldset>

                <div class="wizard-actions">
                    <a href="{{ route('apply.start') }}" class="btn btn-ghost">Back</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn-primary btn-lg">
                        Continue @include('partials.icon', ['name' => 'arrow-right'])
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection
