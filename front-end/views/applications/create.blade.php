@extends('layouts.app')

@section('title', 'New application')
@section('heading', 'New application')

@section('content')

    <div class="page-head container-narrow">
        <h1>New application for regularization</h1>
        <p class="lede">
            An existing occupant whose possession has not been regularized may be treated as a
            tenant, provided possession was taken before {{ $cutoff->format('d F Y') }}.
            <span class="clause">Clause 3(ii)(a)</span>
        </p>
    </div>

    <form method="POST" action="{{ route('applications.store') }}" id="intakeForm" novalidate>
        @csrf

        {{-- ---------------- Applicant ---------------- --}}
        <div class="card">
            <div class="card-head"><h3>Applicant particulars</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="full_name">Full name <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="input @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name') }}" required maxlength="150">
                        @error('full_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="cnic">CNIC <span class="req">*</span></label>
                        <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                               value="{{ old('cnic') }}" required inputmode="numeric" pattern="[0-9]{13}"
                               maxlength="13" placeholder="3520112345671">
                        <p class="hint">13 digits, no dashes.</p>
                        @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="parentage_type">Parentage <span class="req">*</span></label>
                        <select id="parentage_type" name="parentage_type" class="select">
                            <option value="FATHER" @selected(old('parentage_type', 'FATHER') === 'FATHER')>Son / daughter of</option>
                            <option value="HUSBAND" @selected(old('parentage_type') === 'HUSBAND')>Wife of</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="parentage_name">Father&rsquo;s / husband&rsquo;s name <span class="req">*</span></label>
                        <input type="text" id="parentage_name" name="parentage_name"
                               class="input @error('parentage_name') is-invalid @enderror"
                               value="{{ old('parentage_name') }}" required maxlength="150">
                        @error('parentage_name')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="contact">Contact number <span class="req">*</span></label>
                        <input type="tel" id="contact" name="contact" class="input @error('contact') is-invalid @enderror"
                               value="{{ old('contact') }}" required maxlength="20" placeholder="0300-1234567">
                        @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" maxlength="150">
                    </div>
                </div>

                <div class="field">
                    <label for="postal_address">Postal address <span class="req">*</span></label>
                    <textarea id="postal_address" name="postal_address"
                              class="textarea @error('postal_address') is-invalid @enderror"
                              required maxlength="500">{{ old('postal_address') }}</textarea>
                    @error('postal_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <fieldset class="group">
                    <legend>Grounds for remission &mdash; Clause 12</legend>
                    <p class="hint" style="margin-top:-.3rem">
                        The Chairman may assess a nominal rent, or remit rent or arrears, for persons who are
                        indigent, orphans or widows. Tick only what the applicant can evidence.
                    </p>
                    <div class="grid-3">
                        <label style="font-weight:500"><input type="checkbox" name="is_indigent" value="1" @checked(old('is_indigent'))> Indigent</label>
                        <label style="font-weight:500"><input type="checkbox" name="is_widow" value="1" @checked(old('is_widow'))> Widow</label>
                        <label style="font-weight:500"><input type="checkbox" name="is_orphan" value="1" @checked(old('is_orphan'))> Orphan</label>
                    </div>
                </fieldset>
            </div>
        </div>

        {{-- ---------------- Property ---------------- --}}
        <div class="card">
            <div class="card-head"><h3>Property particulars</h3></div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="field">
                        <label for="property_no">Property no. <span class="req">*</span></label>
                        <input type="text" id="property_no" name="property_no"
                               class="input @error('property_no') is-invalid @enderror"
                               value="{{ old('property_no') }}" required maxlength="60">
                        @error('property_no')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="sub_unit_no">Sub-unit no.</label>
                        <input type="text" id="sub_unit_no" name="sub_unit_no" class="input"
                               value="{{ old('sub_unit_no') }}" maxlength="60">
                        <p class="hint">Optional.</p>
                    </div>

                    <div class="field">
                        <label for="property_type">Type <span class="req">*</span></label>
                        <select id="property_type" name="property_type" class="select">
                            @foreach (['HOUSE' => 'House', 'SHOP' => 'Shop', 'BUILDING' => 'Building',
                                       'PLOT' => 'Plot', 'AGRI_LAND' => 'Agricultural land', 'OTHER' => 'Other'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('property_type') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="usage_type">Usage <span class="req">*</span></label>
                        <select id="usage_type" name="usage_type" class="select">
                            @foreach (['RESIDENTIAL' => 'Residential', 'COMMERCIAL' => 'Commercial',
                                       'RESIDENTIAL_CUM_COMMERCIAL' => 'Residential-cum-commercial',
                                       'OTHER' => 'Other'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('usage_type') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="district_id">District <span class="req">*</span></label>
                        <select id="district_id" name="district_id" class="select @error('district_id') is-invalid @enderror" required>
                            <option value="">Select a district</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected((int) old('district_id') === $d->id)>
                                    {{ $d->name }} &mdash; {{ $d->province?->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('district_id')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="city">City / town</label>
                        <input type="text" id="city" name="city" class="input" value="{{ old('city') }}" maxlength="120">
                    </div>

                    <div class="field">
                        <label for="tehsil_id">Tehsil</label>
                        <select id="tehsil_id" name="tehsil_id" class="select">
                            <option value="">Choose a district first</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Province</label>
                        <input type="text" id="provinceEcho" class="input" readonly
                               value="Set by district" tabindex="-1">
                    </div>
                </div>

                <div class="field">
                    <label for="property_address">Property address <span class="req">*</span></label>
                    <textarea id="property_address" name="property_address"
                              class="textarea @error('property_address') is-invalid @enderror"
                              required maxlength="500">{{ old('property_address') }}</textarea>
                    @error('property_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <fieldset class="group">
                    <legend>Revenue identifiers</legend>
                    <div class="grid-3">
                        <div class="field">
                            <label for="khewat_no">Khewat no.</label>
                            <input type="text" id="khewat_no" name="khewat_no" class="input" value="{{ old('khewat_no') }}">
                        </div>
                        <div class="field">
                            <label for="khatooni_no">Khatooni no.</label>
                            <input type="text" id="khatooni_no" name="khatooni_no" class="input" value="{{ old('khatooni_no') }}">
                        </div>
                        <div class="field">
                            <label for="khasra_no">Khasra no.</label>
                            <input type="text" id="khasra_no" name="khasra_no" class="input" value="{{ old('khasra_no') }}">
                        </div>
                    </div>
                </fieldset>

                <fieldset class="group">
                    <legend>Geo coordinates</legend>
                    <div class="grid-2">
                        <div class="field">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" class="input"
                                   value="{{ old('latitude') }}" inputmode="decimal" placeholder="31.5204">
                        </div>
                        <div class="field">
                            <label for="longitude">Longitude</label>
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
                <h3>Area of land</h3>
                <div class="card-actions"><span class="clause">Converted to sqft</span></div>
            </div>
            <div class="card-body">

                <div class="alert alert-info">
                    @include('partials.icon', ['name' => 'info'])
                    <div>
                        <p class="mb-0">
                            A Marla is <strong>272.25 sqft</strong> under the revenue system but
                            <strong>225 sqft</strong> in most urban housing schemes &mdash; a 21% difference
                            that carries straight into the assessed rent. Choose the standard that applies
                            to this property; the factors used are recorded permanently against the application.
                        </p>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="unit_profile_id">Measurement standard <span class="req">*</span></label>
                        <select id="unit_profile_id" name="unit_profile_id" class="select">
                            @foreach ($profiles as $p)
                                <option value="{{ $p->id }}" @selected((int) old('unit_profile_id', $profileId) === (int) $p->id)>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="area_mode">Entry mode <span class="req">*</span></label>
                        <select id="area_mode" name="area_mode" class="select">
                            <option value="SINGLE" @selected(old('area_mode', 'SINGLE') === 'SINGLE')>Single unit</option>
                            <option value="COMPOUND" @selected(old('area_mode') === 'COMPOUND')>Compound (Kanal / Marla / Sarsai)</option>
                        </select>
                    </div>
                </div>

                <div id="areaSingle" class="grid-2">
                    <div class="field">
                        <label for="area_value">Area</label>
                        <input type="text" id="area_value" name="area_value" class="input"
                               value="{{ old('area_value') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="area_unit">Unit</label>
                        <select id="area_unit" name="area_unit" class="select">
                            @foreach ($units as $u)
                                <option value="{{ $u->unit_code }}" @selected(old('area_unit') === $u->unit_code)>
                                    {{ $u->unit_name }} ({{ rtrim(rtrim($u->sqft_per_unit, '0'), '.') }} sqft)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="areaCompound" class="grid-4" hidden>
                    <div class="field">
                        <label for="acres">Acres</label>
                        <input type="text" id="acres" name="acres" class="input" value="{{ old('acres') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="kanals">Kanals</label>
                        <input type="text" id="kanals" name="kanals" class="input" value="{{ old('kanals') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="marlas">Marlas</label>
                        <input type="text" id="marlas" name="marlas" class="input" value="{{ old('marlas') }}" inputmode="decimal">
                    </div>
                    <div class="field">
                        <label for="sarsais">Sarsais</label>
                        <input type="text" id="sarsais" name="sarsais" class="input" value="{{ old('sarsais') }}" inputmode="decimal">
                    </div>
                </div>

                <div class="field">
                    <label for="covered_area_sqft">Covered area (sqft)</label>
                    <input type="text" id="covered_area_sqft" name="covered_area_sqft" class="input"
                           value="{{ old('covered_area_sqft') }}" inputmode="decimal">
                    <p class="hint">Optional. Used where rent is fixed on the covered area.</p>
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
                <h3>Possession</h3>
                <div class="card-actions"><span class="clause">Clause 3(ii)(a)&ndash;(b)</span></div>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="date_of_possession">Date of actual physical possession <span class="req">*</span></label>
                        <input type="date" id="date_of_possession" name="date_of_possession"
                               class="input @error('date_of_possession') is-invalid @enderror"
                               value="{{ old('date_of_possession') }}" required
                               max="{{ $cutoff->toDateString() }}">
                        <p class="hint">Must be on or before {{ $cutoff->format('d-m-Y') }}.</p>
                        @error('date_of_possession')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="possession_nature">Nature of possession <span class="req">*</span></label>
                        <select id="possession_nature" name="possession_nature" class="select">
                            @foreach (['SELF' => 'Self', 'INHERITED' => 'Inherited', 'PURCHASED' => 'Purchased',
                                       'ALLOTTED' => 'Allotted', 'OTHER' => 'Other'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('possession_nature') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="date_of_judicial_verdict">Date of judicial verdict / declaration</label>
                        <input type="date" id="date_of_judicial_verdict" name="date_of_judicial_verdict"
                               class="input" value="{{ old('date_of_judicial_verdict') }}">
                        <p class="hint">
                            If earlier than 01-07-2000 or the date of occupation, arrears will run from this date.
                        </p>
                    </div>

                    <div class="field">
                        <label for="judicial_reference">Court / case reference</label>
                        <input type="text" id="judicial_reference" name="judicial_reference" class="input"
                               value="{{ old('judicial_reference') }}" maxlength="150">
                    </div>
                </div>

                <div class="field">
                    <label for="possession_description">How possession was taken</label>
                    <textarea id="possession_description" name="possession_description"
                              class="textarea" maxlength="2000">{{ old('possession_description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="muted" style="font-size:.88rem">
                    On creation the application is saved as a <strong>draft</strong>. Evidence of possession
                    and the Rs. 5,000 processing fee instrument are recorded on the next screen; the
                    application can only be submitted once both are on record.
                </p>
                <div class="btn-row">
                    <button type="submit" class="btn btn-primary btn-lg">Create application</button>
                    <a href="{{ route('applications.index') }}" class="btn btn-ghost">Cancel</a>
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
                pTotal.textContent = d.sqft_human + ' sqft';
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

    function loadTehsils() {
        if (!districtSel || !tehsilSel) return;
        var id = districtSel.value;
        tehsilSel.innerHTML = '<option value="">Choose a district first</option>';
        if (!id) { if (provinceBox) provinceBox.value = 'Set by district'; return; }

        fetch(tehsilUrl + '?district=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (provinceBox) provinceBox.value = d.province || '';
                tehsilSel.innerHTML = '<option value="">Not listed</option>';
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
