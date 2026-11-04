@extends('layouts.app')

@section('title', __('New application'))
@section('heading', __('New application'))

@section('content')

    <div class="page-head container-narrow">
        <h1>{{ __('New application for regularization') }}</h1>
        <p class="lede">
            {{ __('An existing occupant whose possession has not been regularized may be treated as a tenant, provided possession was taken before :date.', ['date' => $cutoff->translatedFormat('d F Y')]) }}
            <span class="clause">{{ __('Clause 3(ii)(a)') }}</span>
        </p>
    </div>

    <form method="POST" action="{{ route('applications.store') }}" id="intakeForm" novalidate>
        @csrf

        {{-- ---------------- Applicant ---------------- --}}
        <div class="card">
            <div class="card-head"><h3>{{ __('Applicant particulars') }}</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="full_name">{{ __('Full name') }} <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="input @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name') }}" required maxlength="150">
                        @error('full_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="cnic">{{ __('CNIC') }} <span class="req">*</span></label>
                        <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                               value="{{ old('cnic') }}" required inputmode="numeric" pattern="[0-9]{13}"
                               maxlength="13" placeholder="3520112345671">
                        <p class="hint">{{ __('13 digits, no dashes.') }}</p>
                        @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="parentage_type">{{ __('Parentage') }} <span class="req">*</span></label>
                        <select id="parentage_type" name="parentage_type" class="select">
                            <option value="FATHER" @selected(old('parentage_type', 'FATHER') === 'FATHER')>{{ __('Son / daughter of') }}</option>
                            <option value="HUSBAND" @selected(old('parentage_type') === 'HUSBAND')>{{ __('Wife of') }}</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="parentage_name">{{ __("Father's / husband's name") }} <span class="req">*</span></label>
                        <input type="text" id="parentage_name" name="parentage_name"
                               class="input @error('parentage_name') is-invalid @enderror"
                               value="{{ old('parentage_name') }}" required maxlength="150">
                        @error('parentage_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="contact">{{ __('Contact number') }} <span class="req">*</span></label>
                        <input type="tel" id="contact" name="contact" class="input @error('contact') is-invalid @enderror"
                               value="{{ old('contact') }}" required maxlength="20" placeholder="0300-1234567">
                        @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="email">{{ __('Email') }}</label>
                        <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" maxlength="150">
                    </div>
                </div>

                <div class="field">
                    <label for="postal_address">{{ __('Postal address') }} <span class="req">*</span></label>
                    <textarea id="postal_address" name="postal_address"
                              class="textarea @error('postal_address') is-invalid @enderror"
                              required maxlength="500">{{ old('postal_address') }}</textarea>
                    @error('postal_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ---------------- Property ---------------- --}}
        <div class="card">
            <div class="card-head"><h3>{{ __('Property particulars') }}</h3></div>
            <div class="card-body">
                <div class="grid-3">
                    @php
                        $typeOptions = [
                            'OPEN_PLOT' => __('Open Plot'),
                            'RESIDENTIAL' => __('Residential'),
                            'COMMERCIAL' => __('Commercial'),
                            'RESIDENTIAL_CUM_COMMERCIAL' => __('Residential-cum-commercial'),
                            'OTHER' => __('Others'),
                        ];
                    @endphp
                    <div class="field">
                        <label for="property_no">{{ __('Property no.') }}</label>
                        <input type="text" id="property_no" name="property_no"
                               class="input @error('property_no') is-invalid @enderror"
                               value="{{ old('property_no') }}" maxlength="60">
                        <p class="hint">{{ __('Optional.') }}</p>
                        @error('property_no')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="sub_unit_no">{{ __('Sub-unit no.') }}</label>
                        <input type="text" id="sub_unit_no" name="sub_unit_no" class="input"
                               value="{{ old('sub_unit_no') }}" maxlength="60">
                        <p class="hint">{{ __('Optional.') }}</p>
                    </div>

                    <div class="field">
                        <label for="property_type">{{ __('Type') }}</label>
                        <select id="property_type" name="property_type" class="select">
                            @foreach ($typeOptions as $v => $l)
                                <option value="{{ $v }}" @selected(old('property_type', 'RESIDENTIAL') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="usage_type">{{ __('Usage') }}</label>
                        <select id="usage_type" name="usage_type" class="select">
                            @foreach ($typeOptions as $v => $l)
                                <option value="{{ $v }}" @selected(old('usage_type', 'RESIDENTIAL') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="district_id">{{ __('District') }} <span class="req">*</span></label>
                        <select id="district_id" name="district_id" class="select @error('district_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select a district') }}</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected((int) old('district_id') === $d->id)>
                                    {{ __($d->name) }} &mdash; {{ __($d->province?->name ?? '') }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="city">{{ __('City / town') }}</label>
                        <input type="text" id="city" name="city" class="input" value="{{ old('city') }}" maxlength="120">
                    </div>

                    <div class="field">
                        <label for="tehsil_id">{{ __('Tehsil') }}</label>
                        <select id="tehsil_id" name="tehsil_id" class="select">
                            <option value="">{{ __('Choose a district first') }}</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>{{ __('Province') }}</label>
                        <input type="text" id="provinceEcho" class="input" readonly
                               value="{{ __('Set by district') }}" tabindex="-1">
                    </div>
                </div>

                <div class="field">
                    <label for="property_address">{{ __('Property address') }} <span class="req">*</span></label>
                    <textarea id="property_address" name="property_address"
                              class="textarea @error('property_address') is-invalid @enderror"
                              required maxlength="500">{{ old('property_address') }}</textarea>
                    @error('property_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <fieldset class="group">
                    <legend>{{ __('Revenue identifiers') }}</legend>
                    <div class="grid-3">
                        <div class="field">
                            <label for="khewat_no">{{ __('Khewat no.') }}</label>
                            <input type="text" id="khewat_no" name="khewat_no" class="input" value="{{ old('khewat_no') }}">
                        </div>
                        <div class="field">
                            <label for="khatooni_no">{{ __('Khatooni no.') }}</label>
                            <input type="text" id="khatooni_no" name="khatooni_no" class="input" value="{{ old('khatooni_no') }}">
                        </div>
                        <div class="field">
                            <label for="khasra_no">{{ __('Khasra no.') }}</label>
                            <input type="text" id="khasra_no" name="khasra_no" class="input" value="{{ old('khasra_no') }}">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="group">
                    <legend>{{ __('Geo coordinates') }}</legend>
                    <div class="grid-2">
                        <div class="field">
                            <label for="latitude">{{ __('Latitude') }}</label>
                            <input type="text" id="latitude" name="latitude" class="input"
                                   value="{{ old('latitude') }}" inputmode="decimal" placeholder="31.5204">
                        </div>
                        <div class="field">
                            <label for="longitude">{{ __('Longitude') }}</label>
                            <input type="text" id="longitude" name="longitude" class="input"
                                   value="{{ old('longitude') }}" inputmode="decimal" placeholder="74.3587">
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        {{-- ---------------- Area ---------------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Area of land') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Converted to sqft') }}</span></div>
            </div>
            <div class="card-body">

                <div class="alert alert-info">
                    @include('partials.icon', ['name' => 'info'])
                    <div>
                        <p class="mb-0">
                            {!! __('A Marla is <strong>272.25 sqft</strong> under the revenue system but <strong>225 sqft</strong> in most urban housing schemes — a 21% difference that carries straight into the assessed rent. Choose the standard that applies to this property; the factors used are recorded permanently against the application.') !!}
                        </p>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="unit_profile_id">{{ __('Measurement standard') }} <span class="req">*</span></label>
                        <select id="unit_profile_id" name="unit_profile_id" class="select">
                            @foreach ($profiles as $p)
                                <option value="{{ $p->id }}" @selected((int) old('unit_profile_id', $profileId) === (int) $p->id)>
                                    {{ __($p->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="area_mode">{{ __('Entry mode') }} <span class="req">*</span></label>
                        <select id="area_mode" name="area_mode" class="select">
                            <option value="SINGLE" @selected(old('area_mode', 'SINGLE') === 'SINGLE')>{{ __('Single unit') }}</option>
                            <option value="COMPOUND" @selected(old('area_mode') === 'COMPOUND')>{{ __('Compound (Kanal / Marla / Sarsai)') }}</option>
                        </select>
                    </div>
                </div>

                <div id="areaSingle" class="grid-2">
                    <div class="field">
                        <label for="area_value">{{ __('Area') }}</label>
                        <input type="text" id="area_value" name="area_value" class="input"
                               value="{{ old('area_value') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="area_unit">{{ __('Unit') }}</label>
                        <select id="area_unit" name="area_unit" class="select">
                            @foreach ($units as $u)
                                <option value="{{ $u->unit_code }}" @selected(old('area_unit') === $u->unit_code)>
                                    {{ __($u->unit_name) }} ({{ rtrim(rtrim($u->sqft_per_unit, '0'), '.') }} {{ __('sqft') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="areaCompound" class="grid-4" hidden>
                    <div class="field">
                        <label for="acres">{{ __('Acres') }}</label>
                        <input type="text" id="acres" name="acres" class="input" value="{{ old('acres') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="kanals">{{ __('Kanals') }}</label>
                        <input type="text" id="kanals" name="kanals" class="input" value="{{ old('kanals') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="marlas">{{ __('Marlas') }}</label>
                        <input type="text" id="marlas" name="marlas" class="input" value="{{ old('marlas') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="sarsais">{{ __('Sarsais') }}</label>
                        <input type="text" id="sarsais" name="sarsais" class="input" value="{{ old('sarsais') }}" inputmode="decimal">
                    </div>
                </div>

                <div class="field">
                    <label for="covered_area_sqft">{{ __('Covered area (sqft)') }}</label>
                    <input type="text" id="covered_area_sqft" name="covered_area_sqft" class="input"
                           value="{{ old('covered_area_sqft') }}" inputmode="decimal">
                    <p class="hint">{{ __('Optional. Used where rent is fixed on the covered area.') }}</p>
                </div>

                @error('area')<p class="error-text">{{ $message }}</p>@enderror

                <div id="areaPreview" class="alert alert-good" hidden>
                    @include('partials.icon', ['name' => 'check'])
                    <div>
                        <strong id="areaPreviewTotal"></strong>
                        <div id="areaPreviewCompound" class="faint" style="font-size:.82rem"></div>
                        <div id="areaPreviewTrace" class="faint" style="font-size:.78rem;margin-top:.35rem"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------------- Possession ---------------- --}}
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Possession') }}</h3>
                <div class="card-actions"><span class="clause">{{ __('Clause 3(ii)(a)–(b)') }}</span></div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="date_of_possession">{{ __('Date of actual physical possession') }} <span class="req">*</span></label>
                        <input type="date" id="date_of_possession" name="date_of_possession"
                               class="input @error('date_of_possession') is-invalid @enderror"
                               value="{{ old('date_of_possession') }}" required
                               max="{{ $cutoff->toDateString() }}">
                        <p class="hint">{{ __('Must be on or before :date.', ['date' => $cutoff->translatedFormat('d-m-Y')]) }}</p>
                        @error('date_of_possession')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="possession_nature">{{ __('Nature of possession') }} <span class="req">*</span></label>
                        <select id="possession_nature" name="possession_nature" class="select">
                            @foreach (['SELF' => __('Self'), 'INHERITED' => __('Inherited'), 'PURCHASED' => __('Purchased'),
                                       'ALLOTTED' => __('Allotted'), 'OTHER' => __('Other')] as $v => $l)
                                <option value="{{ $v }}" @selected(old('possession_nature') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="date_of_judicial_verdict">{{ __('Date of judicial verdict / declaration') }}</label>
                        <input type="date" id="date_of_judicial_verdict" name="date_of_judicial_verdict"
                               class="input" value="{{ old('date_of_judicial_verdict') }}">
                        <p class="hint">
                            {{ __('If earlier than 01-07-2000 or the date of occupation, arrears will run from this date.') }}
                        </p>
                    </div>

                    <div class="field">
                        <label for="judicial_reference">{{ __('Court / case reference') }}</label>
                        <input type="text" id="judicial_reference" name="judicial_reference" class="input"
                               value="{{ old('judicial_reference') }}" maxlength="150">
                    </div>
                </div>

                <div class="field">
                    <label for="possession_description">{{ __('How possession was taken') }}</label>
                    <textarea id="possession_description" name="possession_description"
                              class="textarea" maxlength="2000">{{ old('possession_description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="muted" style="font-size:.88rem">
                    {!! __('On creation the application is saved as a <strong>draft</strong>. Evidence of possession and the Rs. 5,000 processing fee instrument are recorded on the next screen; the application can only be submitted once both are on record.') !!}
                </p>
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary btn-lg">{{ __('Create application') }}</button>
                    <a href="{{ route('applications.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>
    </form>

@endsection

@push('scripts')
<script>
(function () {
    var modeSel   = document.getElementById('area_mode');
    var single    = document.getElementById('areaSingle');
    var compound  = document.getElementById('areaCompound');
    var preview   = document.getElementById('areaPreview');
    var pTotal    = document.getElementById('areaPreviewTotal');
    var pCompound = document.getElementById('areaPreviewCompound');
    var pTrace    = document.getElementById('areaPreviewTrace');
    var profile   = document.getElementById('unit_profile_id');
    var token     = document.querySelector('meta[name="csrf-token"]').content;
    var i18n      = {
        sqft: @json(__('sqft')),
        chooseDistrict: @json(__('Choose a district first')),
        setByDistrict: @json(__('Set by district')),
        notListed: @json(__('Not listed'))
    };

    function syncMode() {
        var isSingle = modeSel.value === 'SINGLE';
        single.hidden = !isSingle;
        compound.hidden = isSingle;
        recalc();
    }

    function components() {
        if (modeSel.value === 'SINGLE') {
            var v = document.getElementById('area_value').value;
            var u = document.getElementById('area_unit').value;
            if (!v) return null;
            var o = {}; o[u] = v; return o;
        }
        var map = { ACRE: 'acres', KANAL: 'kanals', MARLA: 'marlas', SARSAI: 'sarsais' };
        var out = {}, any = false;
        Object.keys(map).forEach(function (code) {
            var el = document.getElementById(map[code]);
            if (el && el.value) { out[code] = el.value; any = true; }
        });
        return any ? out : null;
    }

    var timer;
    function recalc() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var comp = components();
            if (!comp) { preview.hidden = true; return; }

            fetch(@json(route('tools.area-preview')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ unit_profile_id: profile.value, components: comp })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { preview.hidden = true; return; }
                pTotal.textContent = d.sqft_human + ' ' + i18n.sqft;
                pCompound.textContent = d.compound + '  ·  ' + d.profile;
                pTrace.textContent = d.trace.map(function (t) { return t.expression; }).join('   +   ');
                preview.hidden = false;
            })
            .catch(function () { preview.hidden = true; });
        }, 300);
    }

    // ---- cascading tehsil --------------------------------------------------
    var districtSel = document.getElementById('district_id');
    var tehsilSel   = document.getElementById('tehsil_id');
    var provinceBox = document.getElementById('provinceEcho');
    var tehsilUrl   = @json(route('lookup.tehsils'));
    var savedTehsil = @json(old('tehsil_id'));

    function emptyOption(text) {
        var o = document.createElement('option');
        o.value = ''; o.textContent = text;
        return o;
    }

    function loadTehsils() {
        if (!districtSel || !tehsilSel) return;
        var id = districtSel.value;
        tehsilSel.innerHTML = '';
        tehsilSel.appendChild(emptyOption(i18n.chooseDistrict));
        if (!id) { if (provinceBox) provinceBox.value = i18n.setByDistrict; return; }

        fetch(tehsilUrl + '?district=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (provinceBox) provinceBox.value = d.province || '';
                tehsilSel.innerHTML = '';
                tehsilSel.appendChild(emptyOption(i18n.notListed));
                (d.tehsils || []).forEach(function (t) {
                    var o = document.createElement('option');
                    o.value = t.id; o.textContent = t.name;
                    if (String(savedTehsil) === String(t.id)) o.selected = true;
                    tehsilSel.appendChild(o);
                });
            })
            .catch(function () { /* leave the select empty */ });
    }

    if (districtSel) {
        districtSel.addEventListener('change', loadTehsils);
        if (districtSel.value) loadTehsils();
    }

    modeSel.addEventListener('change', syncMode);
    profile.addEventListener('change', recalc);
    ['area_value', 'area_unit', 'acres', 'kanals', 'marlas', 'sarsais'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', recalc);
        if (el) el.addEventListener('change', recalc);
    });

    syncMode();
})();
</script>
@endpush
