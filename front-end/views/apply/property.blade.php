@extends('layouts.app')

@section('title', __('The property'))
@section('heading', __('Step 2 of 6 — The property'))

@section('content')

@php
    $pr = $draft['property'] ?? [];
    $typeOptions = [
        'OPEN_PLOT' => __('Open Plot'),
        'RESIDENTIAL' => __('Residential'),
        'COMMERCIAL' => __('Commercial'),
        'RESIDENTIAL_CUM_COMMERCIAL' => __('Residential-cum-commercial'),
        'OTHER' => __('Others'),
    ];
@endphp

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>{{ __('The property') }}</h1>
        <p class="lede">{{ __('Only the essentials are required now. Everything else can wait for the office.') }}</p>
    </div>

    <form method="POST" action="{{ route('apply.property.store') }}" id="propertyForm" novalidate>
        @csrf

        <div class="essentials-banner">
            <span aria-hidden="true">★</span>
            <div>
                <strong>{{ __('Required on this step:') }}</strong> {{ __('property address, district, and geo coordinates.') }}
                {{ __('Open the sections below only if you already know the rest.') }}
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>{{ __('Where is the property?') }}</h3></div>
            <div class="card-body">
                <div class="field">
                    <label for="property_address">{{ __('Property address') }} <span class="req">*</span></label>
                    <textarea id="property_address" name="property_address"
                              class="textarea @error('property_address') is-invalid @enderror"
                              required maxlength="500"
                              placeholder="{{ __('Street / mohalla / landmark') }}">{{ old('property_address', $pr['property_address'] ?? '') }}</textarea>
                    @error('property_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="district_id">{{ __('District') }} <span class="req">*</span></label>
                        <select id="district_id" name="district_id"
                                class="select @error('district_id') is-invalid @enderror" required>
                            <option value="">{{ __('Select a district') }}</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}"
                                    @selected((int) old('district_id', $pr['district_id'] ?? 0) === $d->id)>
                                    {{ __($d->name) }} — {{ __($d->province?->name ?? '') }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label>{{ __('Province') }}</label>
                        <input type="text" id="provinceEcho" class="input" readonly
                               value="{{ __('Choose a district') }}" tabindex="-1">
                    </div>
                </div>

                <fieldset class="group">
                    <legend>{{ __('Geo coordinates') }} <span class="req">*</span></legend>
                    <p class="hint mt-0">
                        {{ __('Stand at the property if you can, then tap the button — your phone fills these in.') }}
                    </p>
                    <div class="grid-2">
                        <div class="field">
                            <label for="latitude">{{ __('Latitude') }} <span class="req">*</span></label>
                            <input type="text" id="latitude" name="latitude"
                                   class="input @error('latitude') is-invalid @enderror"
                                   value="{{ old('latitude', $pr['latitude'] ?? '') }}"
                                   inputmode="decimal" placeholder="31.5204" required>
                            @error('latitude')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="longitude">{{ __('Longitude') }} <span class="req">*</span></label>
                            <input type="text" id="longitude" name="longitude"
                                   class="input @error('longitude') is-invalid @enderror"
                                   value="{{ old('longitude', $pr['longitude'] ?? '') }}"
                                   inputmode="decimal" placeholder="74.3587" required>
                            @error('longitude')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" id="geoBtn">
                        @include('partials.icon', ['name' => 'map']) {{ __('Use my current location') }}
                    </button>
                    <span id="geoMsg" class="hint ms-2"></span>
                </fieldset>
            </div>
        </div>

        <details class="optional-panel">
            <summary>
                {{ __('More property details') }}
                <span class="badge badge-neutral">{{ __('Optional — office can fill later') }}</span>
            </summary>
            <div class="optional-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="property_no">{{ __('Property number') }}</label>
                        <input type="text" id="property_no" name="property_no" class="input"
                               value="{{ old('property_no', $pr['property_no'] ?? '') }}" maxlength="60">
                    </div>
                    <div class="field">
                        <label for="sub_unit_no">{{ __('Sub-unit number') }}</label>
                        <input type="text" id="sub_unit_no" name="sub_unit_no" class="input"
                               value="{{ old('sub_unit_no', $pr['sub_unit_no'] ?? '') }}" maxlength="60">
                    </div>
                    <div class="field">
                        <label for="property_type">{{ __('What kind of property?') }}</label>
                        <select id="property_type" name="property_type" class="select">
                            <option value="">{{ __('Not sure yet') }}</option>
                            @foreach ($typeOptions as $v => $l)
                                <option value="{{ $v }}" @selected(old('property_type', $pr['property_type'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="usage_type">{{ __('How is it used?') }}</label>
                        <select id="usage_type" name="usage_type" class="select">
                            <option value="">{{ __('Not sure yet') }}</option>
                            @foreach ($typeOptions as $v => $l)
                                <option value="{{ $v }}" @selected(old('usage_type', $pr['usage_type'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="city">{{ __('City or town') }}</label>
                        <input type="text" id="city" name="city" class="input"
                               value="{{ old('city', $pr['city'] ?? '') }}" maxlength="120">
                    </div>
                    <div class="field">
                        <label for="tehsil_id">{{ __('Tehsil') }}</label>
                        <select id="tehsil_id" name="tehsil_id" class="select">
                            <option value="">{{ __('Choose a district first') }}</option>
                        </select>
                        <p class="hint" id="tehsilHint">{{ __('Filled in once you pick a district.') }}</p>
                    </div>
                    <div class="field">
                        <label for="mouza_name">{{ __('Mouza') }}</label>
                        <input type="text" id="mouza_name" name="mouza_name" class="input"
                               value="{{ old('mouza_name', $pr['mouza_name'] ?? '') }}"
                               maxlength="150" list="mouzaList">
                        <datalist id="mouzaList"></datalist>
                    </div>
                </div>
            </div>
        </details>

        <details class="optional-panel">
            <summary>
                {{ __('Area of the Property / Land') }}
                <span class="badge badge-neutral">{{ __('Optional') }}</span>
            </summary>
            <div class="optional-body">
                <p class="muted text-[.9rem] mb-3">
                    {!! __('Default standard is <strong>1 Marla = 225 sqft</strong> unless you choose another.') !!}
                </p>
                <div class="grid-2">
                    <div class="field">
                        <label for="area_mode">{{ __('How would you like to enter it?') }}</label>
                        <select id="area_mode" name="area_mode" class="select">
                            <option value="SINGLE" @selected(old('area_mode', $pr['area_mode'] ?? 'SINGLE') === 'SINGLE')>
                                {{ __('One figure in one unit') }}
                            </option>
                            <option value="COMPOUND" @selected(old('area_mode', $pr['area_mode'] ?? '') === 'COMPOUND')>
                                {{ __('Kanal, Marla and Sarsai together') }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="unit_profile_id">{{ __('Measurement standard') }}</label>
                        <select id="unit_profile_id" name="unit_profile_id" class="select">
                            @foreach ($profiles as $p)
                                <option value="{{ $p->id }}"
                                    @selected((int) old('unit_profile_id', $pr['unit_profile_id'] ?? $profileId) === (int) $p->id)>
                                    {{ __($p->name ?? '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="areaSingle" class="grid-2">
                    <div class="field">
                        <label for="area_value">{{ __('Area') }}</label>
                        <input type="text" id="area_value" name="area_value" class="input"
                               value="{{ old('area_value', $pr['area_value'] ?? '') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="area_unit">{{ __('Unit') }}</label>
                        <select id="area_unit" name="area_unit" class="select">
                            @foreach ($units as $u)
                                <option value="{{ $u->unit_code }}"
                                    @selected(old('area_unit', $pr['area_unit'] ?? '') === $u->unit_code)>
                                    {{ __($u->unit_name ?? '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="areaCompound" class="grid-4" hidden>
                    @foreach (['acres' => __('Acres'), 'kanals' => __('Kanals'), 'marlas' => __('Marlas'), 'sarsais' => __('Sarsais')] as $f => $l)
                        <div class="field">
                            <label for="{{ $f }}">{{ $l }}</label>
                            <input type="text" id="{{ $f }}" name="{{ $f }}" class="input"
                                   value="{{ old($f, $pr[$f] ?? '') }}" inputmode="decimal">
                        </div>
                    @endforeach
                </div>

                <div id="areaPreview" class="alert alert-good" hidden>
                    @include('partials.icon', ['name' => 'check'])
                    <div class="min-w-0">
                        <strong id="areaTotal"></strong>
                        <div id="areaCompoundLabel" class="faint text-[.82rem]"></div>
                        <div id="areaTrace" class="faint text-[.78rem] mt-1"></div>
                    </div>
                </div>

                <div class="grid-3 mt-3">
                    <div class="field">
                        <label for="total_area_sqft">{{ __('Total Area (sqft)') }}</label>
                        <input type="text" id="total_area_sqft" name="total_area_sqft" class="input"
                               value="{{ old('total_area_sqft', $pr['total_area_sqft'] ?? '') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="covered_area_sqft">{{ __('Covered Area (sqft)') }}</label>
                        <input type="text" id="covered_area_sqft" name="covered_area_sqft" class="input"
                               value="{{ old('covered_area_sqft', $pr['covered_area_sqft'] ?? '') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="open_area_sqft">{{ __('Open Area (sqft)') }}</label>
                        <input type="text" id="open_area_sqft" name="open_area_sqft" class="input"
                               value="{{ old('open_area_sqft', $pr['open_area_sqft'] ?? '') }}" inputmode="decimal">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="number_of_floors">{{ __('Number of floors') }}</label>
                        <input type="number" id="number_of_floors" name="number_of_floors" class="input"
                               value="{{ old('number_of_floors', $pr['number_of_floors'] ?? '') }}"
                               min="0" max="100" inputmode="numeric">
                    </div>
                    <div class="field">
                        <label>{{ __('Floor-wise covered area (sqft)') }}</label>
                        <div class="grid-2" id="floorWiseFields">
                            @for ($i = 1; $i <= 4; $i++)
                                <input type="text" name="floor_wise_covered_area[]" class="input"
                                       placeholder="{{ __('Floor :n', ['n' => $i]) }}"
                                       value="{{ old('floor_wise_covered_area.'.($i-1), $pr['floor_wise_covered_area'][$i-1] ?? '') }}"
                                       inputmode="decimal">
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </details>

        <details class="optional-panel">
            <summary>
                {{ __('Revenue record numbers') }}
                <span class="badge badge-neutral">{{ __('Optional') }}</span>
            </summary>
            <div class="optional-body">
                <div class="grid-2">
                    @foreach (['khewat_no' => __('Khewat no.'), 'khatooni_no' => __('Khatooni no.')] as $f => $l)
                        <div class="field">
                            <label for="{{ $f }}">{{ $l }}</label>
                            <input type="text" id="{{ $f }}" name="{{ $f }}" class="input"
                                   value="{{ old($f, $pr[$f] ?? '') }}" maxlength="40">
                        </div>
                    @endforeach
                    <div class="field">
                        <label for="khasra_no">{{ __('Khasra / Survey No.') }}</label>
                        <input type="text" id="khasra_no" name="khasra_no" class="input"
                               value="{{ old('khasra_no', $pr['khasra_no'] ?? '') }}" maxlength="40">
                    </div>
                    <div class="field">
                        <label for="survey_no">{{ __('Survey No. (if separate)') }}</label>
                        <input type="text" id="survey_no" name="survey_no" class="input"
                               value="{{ old('survey_no', $pr['survey_no'] ?? '') }}" maxlength="60">
                    </div>
                </div>
            </div>
        </details>

        <div class="wizard-actions">
            <a href="{{ route('apply.applicant') }}" class="btn btn-ghost">{{ __('Back') }}</a>
            <span class="spacer"></span>
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __('Continue') }} @include('partials.icon', ['name' => 'arrow-right'])
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var T = {
        sqft:                @json(__('That is :sqft square feet')),
        chooseDistrict:      @json(__('Choose a district')),
        chooseDistrictFirst: @json(__('Choose a district first')),
        notListed:           @json(__('Not sure / not listed')),
        tehsilCount:         @json(__(':count tehsil(s) in this district.')),
        noTehsils:           @json(__('No tehsil list loaded for this district yet — you can leave this blank.')),
        tehsilsFailed:       @json(__('Could not load tehsils.')),
        locating:            @json(__('Finding your location…')),
        located:             @json(__('Location filled in.')),
        locateFailed:        @json(__('Could not get your location. You can type it in instead.')),
        noGeo:               @json(__('Your browser cannot provide a location.'))
    };

    function optionHtml(text) {
        var o = document.createElement('option');
        o.value = '';
        o.textContent = text;
        return o.outerHTML;
    }

    var mode     = document.getElementById('area_mode');
    var single   = document.getElementById('areaSingle');
    var compound = document.getElementById('areaCompound');
    var preview  = document.getElementById('areaPreview');
    var total    = document.getElementById('areaTotal');
    var cLabel   = document.getElementById('areaCompoundLabel');
    var trace    = document.getElementById('areaTrace');
    var profile  = document.getElementById('unit_profile_id');
    var token    = document.querySelector('meta[name="csrf-token"]').content;
    var url      = @json(route('tools.area-preview'));
    var timer;

    function sync() {
        if (!mode || !single || !compound) return;
        var isSingle = mode.value === 'SINGLE';
        single.hidden = !isSingle;
        compound.hidden = isSingle;
        recalc();
    }

    function components() {
        if (!mode) return null;
        if (mode.value === 'SINGLE') {
            var v = document.getElementById('area_value').value;
            if (!v) return null;
            var o = {}; o[document.getElementById('area_unit').value] = v; return o;
        }
        var map = { ACRE: 'acres', KANAL: 'kanals', MARLA: 'marlas', SARSAI: 'sarsais' };
        var out = {}, any = false;
        Object.keys(map).forEach(function (code) {
            var el = document.getElementById(map[code]);
            if (el && el.value) { out[code] = el.value; any = true; }
        });
        return any ? out : null;
    }

    function recalc() {
        clearTimeout(timer);
        if (!preview || !profile) return;
        var comp = components();
        if (!comp) { preview.hidden = true; return; }

        timer = setTimeout(function () {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ unit_profile_id: profile.value, components: comp })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) { preview.hidden = true; return; }
                total.textContent = T.sqft.replace(':sqft', d.sqft_human);
                cLabel.textContent = d.compound + '  ·  ' + d.profile;
                trace.textContent = d.trace.map(function (t) { return t.expression; }).join('   +   ');
                preview.hidden = false;
            })
            .catch(function () { preview.hidden = true; });
        }, 300);
    }

    if (mode) mode.addEventListener('change', sync);
    if (profile) profile.addEventListener('change', recalc);
    ['area_value', 'area_unit', 'acres', 'kanals', 'marlas', 'sarsais'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', recalc);
        el.addEventListener('change', recalc);
    });

    var districtSel = document.getElementById('district_id');
    var tehsilSel   = document.getElementById('tehsil_id');
    var provinceBox = document.getElementById('provinceEcho');
    var tehsilHint  = document.getElementById('tehsilHint');
    var mouzaList   = document.getElementById('mouzaList');
    var tehsilUrl   = @json(route('lookup.tehsils'));
    var mouzaUrl    = @json(route('lookup.mouzas'));
    var savedTehsil = @json(old('tehsil_id', $pr['tehsil_id'] ?? null));

    function loadTehsils() {
        if (!districtSel || !tehsilSel) return;
        var id = districtSel.value;
        tehsilSel.innerHTML = optionHtml(T.chooseDistrictFirst);
        if (mouzaList) mouzaList.innerHTML = '';
        if (!id) { if (provinceBox) provinceBox.value = T.chooseDistrict; return; }

        fetch(tehsilUrl + '?district=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (provinceBox) provinceBox.value = d.province || '';
                tehsilSel.innerHTML = optionHtml(T.notListed);
                (d.tehsils || []).forEach(function (t) {
                    var o = document.createElement('option');
                    o.value = t.id; o.textContent = t.name;
                    if (String(savedTehsil) === String(t.id)) o.selected = true;
                    tehsilSel.appendChild(o);
                });
                if (tehsilHint) {
                    tehsilHint.textContent = d.tehsils && d.tehsils.length
                        ? T.tehsilCount.replace(':count', d.tehsils.length)
                        : T.noTehsils;
                }
                loadMouzas();
            })
            .catch(function () { if (tehsilHint) tehsilHint.textContent = T.tehsilsFailed; });
    }

    function loadMouzas() {
        if (!mouzaList || !tehsilSel) return;
        mouzaList.innerHTML = '';
        if (!tehsilSel.value) return;
        fetch(mouzaUrl + '?tehsil=' + encodeURIComponent(tehsilSel.value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                (d.mouzas || []).forEach(function (m) {
                    var o = document.createElement('option');
                    o.value = m.name;
                    mouzaList.appendChild(o);
                });
            })
            .catch(function () {});
    }

    if (districtSel) {
        districtSel.addEventListener('change', loadTehsils);
        if (districtSel.value) loadTehsils();
    }
    if (tehsilSel) tehsilSel.addEventListener('change', loadMouzas);

    var geoBtn = document.getElementById('geoBtn');
    var geoMsg = document.getElementById('geoMsg');
    if (geoBtn && navigator.geolocation) {
        geoBtn.addEventListener('click', function () {
            geoMsg.textContent = T.locating;
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
                document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
                geoMsg.textContent = T.located;
            }, function () {
                geoMsg.textContent = T.locateFailed;
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    } else if (geoBtn) {
        geoBtn.disabled = true;
        if (geoMsg) geoMsg.textContent = T.noGeo;
    }

    sync();
})();
</script>
@endpush
