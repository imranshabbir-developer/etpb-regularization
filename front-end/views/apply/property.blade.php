@extends('layouts.app')

@section('title', 'The property')
@section('heading', 'Step 2 of 6 — The property')

@section('content')

@php $pr = $draft['property'] ?? []; @endphp

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>The property</h1>
        <p class="lede">Where it is, what it is, and how big it is.</p>
    </div>

    <form method="POST" action="{{ route('apply.property.store') }}" id="propertyForm" novalidate>
        @csrf

        <div class="card">
            <div class="card-head"><h3>Identify the property</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="property_no">Property number <span class="req">*</span></label>
                        <input type="text" id="property_no" name="property_no"
                               class="input @error('property_no') is-invalid @enderror"
                               value="{{ old('property_no', $pr['property_no'] ?? '') }}" required maxlength="60">
                        <p class="hint">As it appears on your papers or the ETPB record.</p>
                        @error('property_no')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="sub_unit_no">Sub-unit number</label>
                        <input type="text" id="sub_unit_no" name="sub_unit_no" class="input"
                               value="{{ old('sub_unit_no', $pr['sub_unit_no'] ?? '') }}" maxlength="60">
                        <p class="hint">Only if the property is divided. Leave blank otherwise.</p>
                    </div>

                    <div class="field">
                        <label for="property_type">What kind of property? <span class="req">*</span></label>
                        <select id="property_type" name="property_type" class="select">
                            @foreach (['HOUSE' => 'House', 'SHOP' => 'Shop', 'BUILDING' => 'Building',
                                       'PLOT' => 'Plot', 'AGRI_LAND' => 'Agricultural land',
                                       'OTHER' => 'Something else'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('property_type', $pr['property_type'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="usage_type">How is it used? <span class="req">*</span></label>
                        <select id="usage_type" name="usage_type" class="select">
                            @foreach (['RESIDENTIAL' => 'I live in it', 'COMMERCIAL' => 'Business use',
                                       'RESIDENTIAL_CUM_COMMERCIAL' => 'Both home and business',
                                       'OTHER' => 'Something else'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('usage_type', $pr['usage_type'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="property_address">Full address <span class="req">*</span></label>
                    <textarea id="property_address" name="property_address"
                              class="textarea @error('property_address') is-invalid @enderror"
                              required maxlength="500">{{ old('property_address', $pr['property_address'] ?? '') }}</textarea>
                    @error('property_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="grid-3">
                    <div class="field">
                        <label for="district_id">District <span class="req">*</span></label>
                        <select id="district_id" name="district_id"
                                class="select @error('district_id') is-invalid @enderror" required>
                            <option value="">Select a district</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}"
                                    @selected((int) old('district_id', $pr['district_id'] ?? 0) === $d->id)>
                                    {{ $d->name }} — {{ $d->province?->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="city">City or town</label>
                        <input type="text" id="city" name="city" class="input"
                               value="{{ old('city', $pr['city'] ?? '') }}" maxlength="120">
                    </div>

                    <div class="field">
                        <label for="tehsil_id">Tehsil</label>
                        <select id="tehsil_id" name="tehsil_id" class="select">
                            <option value="">Choose a district first</option>
                        </select>
                        <p class="hint" id="tehsilHint">Filled in once you pick a district.</p>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="mouza_name">Mouza</label>
                        <input type="text" id="mouza_name" name="mouza_name" class="input"
                               value="{{ old('mouza_name', $pr['mouza_name'] ?? '') }}"
                               maxlength="150" list="mouzaList">
                        <datalist id="mouzaList"></datalist>
                        <p class="hint">If you know it. Type it in, or pick from the list where one exists.</p>
                    </div>
                    <div class="field">
                        <label>Province</label>
                        <input type="text" id="provinceEcho" class="input" readonly
                               value="Choose a district" tabindex="-1">
                        <p class="hint">Set automatically by the district.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Area ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>How big is it?</h3>
                <div class="card-actions"><span class="clause">Converted to sqft</span></div>
            </div>
            <div class="card-body">
                <p class="muted text-[.9rem]">
                    Enter the area however your papers record it. We will convert it to square feet
                    and show you the working.
                </p>

                <div class="grid-2">
                    <div class="field">
                        <label for="area_mode">How would you like to enter it?</label>
                        <select id="area_mode" name="area_mode" class="select">
                            <option value="SINGLE" @selected(old('area_mode', $pr['area_mode'] ?? 'SINGLE') === 'SINGLE')>
                                One figure in one unit
                            </option>
                            <option value="COMPOUND" @selected(old('area_mode', $pr['area_mode'] ?? '') === 'COMPOUND')>
                                Kanal, Marla and Sarsai together
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="unit_profile_id">Measurement standard</label>
                        <select id="unit_profile_id" name="unit_profile_id" class="select">
                            @foreach ($profiles as $p)
                                <option value="{{ $p->id }}"
                                    @selected((int) old('unit_profile_id', $pr['unit_profile_id'] ?? $profileId) === (int) $p->id)>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="hint">Leave this as it is unless your district office tells you otherwise.</p>
                    </div>
                </div>

                <div id="areaSingle" class="grid-2">
                    <div class="field">
                        <label for="area_value">Area</label>
                        <input type="text" id="area_value" name="area_value"
                               class="input @error('area_value') is-invalid @enderror"
                               value="{{ old('area_value', $pr['area_value'] ?? '') }}" inputmode="decimal">
                        @error('area_value')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="field">
                        <label for="area_unit">Unit</label>
                        <select id="area_unit" name="area_unit" class="select">
                            @foreach ($units as $u)
                                <option value="{{ $u->unit_code }}"
                                    @selected(old('area_unit', $pr['area_unit'] ?? '') === $u->unit_code)>
                                    {{ $u->unit_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="areaCompound" class="grid-4" hidden>
                    @foreach (['acres' => 'Acres', 'kanals' => 'Kanals', 'marlas' => 'Marlas', 'sarsais' => 'Sarsais'] as $f => $l)
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

                <div class="field">
                    <label for="covered_area_sqft">Covered area in square feet</label>
                    <input type="text" id="covered_area_sqft" name="covered_area_sqft" class="input"
                           value="{{ old('covered_area_sqft', $pr['covered_area_sqft'] ?? '') }}" inputmode="decimal">
                    <p class="hint">Optional — the built-up part, if you know it.</p>
                </div>
            </div>
        </div>

        {{-- ---------- Revenue and geo ---------- --}}
        <div class="card">
            <div class="card-head"><h3>Revenue record and location</h3></div>
            <div class="card-body">
                <p class="muted text-[.9rem]">All optional, but they help the officer find the record faster.</p>

                <div class="grid-3">
                    @foreach (['khewat_no' => 'Khewat no.', 'khatooni_no' => 'Khatooni no.', 'khasra_no' => 'Khasra no.'] as $f => $l)
                        <div class="field">
                            <label for="{{ $f }}">{{ $l }}</label>
                            <input type="text" id="{{ $f }}" name="{{ $f }}" class="input"
                                   value="{{ old($f, $pr[$f] ?? '') }}" maxlength="40">
                        </div>
                    @endforeach
                </div>

                <fieldset class="group">
                    <legend>Geo coordinates</legend>
                    <p class="hint mt-0">
                        The department asks for these. If you are standing at the property on a phone,
                        tap the button and your device will fill them in.
                    </p>
                    <div class="grid-2">
                        <div class="field">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="input"
                                   value="{{ old('latitude', $pr['latitude'] ?? '') }}"
                                   inputmode="decimal" placeholder="31.5204">
                        </div>
                        <div class="field">
                            <label for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" class="input"
                                   value="{{ old('longitude', $pr['longitude'] ?? '') }}"
                                   inputmode="decimal" placeholder="74.3587">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" id="geoBtn">
                        @include('partials.icon', ['name' => 'map']) Use my current location
                    </button>
                    <span id="geoMsg" class="hint ms-2"></span>
                </fieldset>

                <div class="wizard-actions">
                    <a href="{{ route('apply.applicant') }}" class="btn btn-ghost">Back</a>
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

@push('scripts')
<script>
(function () {
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
        var isSingle = mode.value === 'SINGLE';
        single.hidden = !isSingle;
        compound.hidden = isSingle;
        recalc();
    }

    function components() {
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
                total.textContent = 'That is ' + d.sqft_human + ' square feet';
                cLabel.textContent = d.compound + '  ·  ' + d.profile;
                trace.textContent = d.trace.map(function (t) { return t.expression; }).join('   +   ');
                preview.hidden = false;
            })
            .catch(function () { preview.hidden = true; });
        }, 300);
    }

    mode.addEventListener('change', sync);
    profile.addEventListener('change', recalc);
    ['area_value', 'area_unit', 'acres', 'kanals', 'marlas', 'sarsais'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', recalc);
        el.addEventListener('change', recalc);
    });

    // ---- cascading geography ----------------------------------------------
    var districtSel = document.getElementById('district_id');
    var tehsilSel   = document.getElementById('tehsil_id');
    var provinceBox = document.getElementById('provinceEcho');
    var tehsilHint  = document.getElementById('tehsilHint');
    var mouzaList   = document.getElementById('mouzaList');
    var tehsilUrl   = @json(route('lookup.tehsils'));
    var mouzaUrl    = @json(route('lookup.mouzas'));
    var savedTehsil = @json(old('tehsil_id', $pr['tehsil_id'] ?? null));

    function loadTehsils() {
        var id = districtSel.value;
        tehsilSel.innerHTML = '<option value="">Choose a district first</option>';
        mouzaList.innerHTML = '';
        if (!id) { provinceBox.value = 'Choose a district'; return; }

        fetch(tehsilUrl + '?district=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                provinceBox.value = d.province || '';
                tehsilSel.innerHTML = '<option value="">Not sure / not listed</option>';
                (d.tehsils || []).forEach(function (t) {
                    var o = document.createElement('option');
                    o.value = t.id; o.textContent = t.name;
                    if (String(savedTehsil) === String(t.id)) o.selected = true;
                    tehsilSel.appendChild(o);
                });
                tehsilHint.textContent = d.tehsils && d.tehsils.length
                    ? d.tehsils.length + ' tehsil(s) in this district.'
                    : 'No tehsil list loaded for this district yet — you can leave this blank.';
                loadMouzas();
            })
            .catch(function () { tehsilHint.textContent = 'Could not load tehsils.'; });
    }

    function loadMouzas() {
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
            .catch(function () { /* free text still works */ });
    }

    if (districtSel) {
        districtSel.addEventListener('change', loadTehsils);
        if (districtSel.value) loadTehsils();
    }
    if (tehsilSel) tehsilSel.addEventListener('change', loadMouzas);

    // ---- geolocation ------------------------------------------------------
    var geoBtn = document.getElementById('geoBtn');
    var geoMsg = document.getElementById('geoMsg');
    if (geoBtn && navigator.geolocation) {
        geoBtn.addEventListener('click', function () {
            geoMsg.textContent = 'Finding your location…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
                document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
                geoMsg.textContent = 'Location filled in.';
            }, function () {
                geoMsg.textContent = 'Could not get your location. You can type it in instead.';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    } else if (geoBtn) {
        geoBtn.disabled = true;
        geoMsg.textContent = 'Your browser cannot provide a location.';
    }

    sync();
})();
</script>
@endpush
