@extends('layouts.app')

@section('title', 'Others and courts')
@section('heading', 'Step 5 of 6 — Others and courts')

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>Anyone else, and any court case</h1>
        <p class="lede">
            The department needs to know whether someone else is also in occupation, and
            whether any court is involved.
        </p>
    </div>

    @if ($application->occupantOffers->isNotEmpty() || $application->litigations->isNotEmpty())
        <div class="card">
            <div class="card-head"><h3>Already declared</h3></div>
            <div class="card-body">
                @foreach ($application->occupantOffers as $o)
                    <div class="inline-list">
                        <span class="badge badge-neutral">Other occupant</span>
                        <strong>{{ $o->occupant_name }}</strong>
                        @if ((float) $o->rent_offered > 0)
                            <span class="faint">offering Rs. {{ number_format((float) $o->rent_offered, 0) }}</span>
                        @endif
                    </div>
                @endforeach
                @foreach ($application->litigations as $l)
                    <div class="inline-list mt-1">
                        <span class="badge badge-warn">Court case</span>
                        <strong>{{ $l->case_no }}</strong>
                        <span class="faint">{{ $l->court_name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('apply.occupants.store', $application) }}" novalidate>
        @csrf

        {{-- ---------- Other occupants ---------- --}}
        <div class="card">
            <div class="card-head"><h3>Is anyone else in occupation?</h3></div>
            <div class="card-body">
                <div class="field">
                    <div class="btn-row">
                        <label class="btn btn-outline" style="cursor:pointer">
                            <input type="radio" name="has_other_occupants" value="no" class="me-1"
                                   @checked(old('has_other_occupants', 'no') === 'no')
                                   data-toggle="occupantBlock" data-show="0">
                            No, only me
                        </label>
                        <label class="btn btn-outline" style="cursor:pointer">
                            <input type="radio" name="has_other_occupants" value="yes" class="me-1"
                                   @checked(old('has_other_occupants') === 'yes')
                                   data-toggle="occupantBlock" data-show="1">
                            Yes, someone else too
                        </label>
                    </div>
                    @error('has_other_occupants')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div id="occupantBlock" hidden>
                    <div class="grid-2">
                        <div class="field">
                            <label for="occupant_name">Their name</label>
                            <input type="text" id="occupant_name" name="occupant_name" class="input"
                                   value="{{ old('occupant_name') }}" maxlength="150">
                            @error('occupant_name')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="occupant_cnic">Their CNIC</label>
                            <input type="text" id="occupant_cnic" name="occupant_cnic" class="input"
                                   value="{{ old('occupant_cnic') }}" inputmode="numeric"
                                   pattern="[0-9]{13}" maxlength="13">
                        </div>
                        <div class="field">
                            <label for="occupant_contact">Their contact</label>
                            <input type="text" id="occupant_contact" name="occupant_contact"
                                   class="input" value="{{ old('occupant_contact') }}" maxlength="20">
                        </div>
                        <div class="field">
                            <label for="portion_occupied">Which part do they occupy?</label>
                            <input type="text" id="portion_occupied" name="portion_occupied"
                                   class="input" value="{{ old('portion_occupied') }}" maxlength="200">
                        </div>
                    </div>
                    <div class="field">
                        <label for="rent_offered">Rent they have offered, if any (Rs.)</label>
                        <input type="text" id="rent_offered" name="rent_offered" class="input"
                               value="{{ old('rent_offered') }}" inputmode="decimal">
                        <p class="hint">Leave blank if they have not offered anything.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Litigation ---------- --}}
        <div class="card">
            <div class="card-head">
                <h3>Is any court case going on about this property?</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    @include('partials.icon', ['name' => 'info'])
                    <div>
                        <p class="mb-0">
                            Answer honestly. While a case is pending or a stay is in force the
                            department <strong>cannot proceed</strong> with your application, and it
                            will resume once the case is over. Concealing a case will only cause
                            problems later.
                        </p>
                    </div>
                </div>

                <div class="field">
                    <div class="btn-row">
                        <label class="btn btn-outline" style="cursor:pointer">
                            <input type="radio" name="has_court_case" value="no" class="me-1"
                                   @checked(old('has_court_case', 'no') === 'no')
                                   data-toggle="courtBlock" data-show="0">
                            No
                        </label>
                        <label class="btn btn-outline" style="cursor:pointer">
                            <input type="radio" name="has_court_case" value="yes" class="me-1"
                                   @checked(old('has_court_case') === 'yes')
                                   data-toggle="courtBlock" data-show="1">
                            Yes
                        </label>
                    </div>
                    @error('has_court_case')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div id="courtBlock" hidden>
                    <div class="grid-2">
                        <div class="field">
                            <label for="court_name">Which court?</label>
                            <input type="text" id="court_name" name="court_name" class="input"
                                   value="{{ old('court_name') }}" maxlength="200">
                            @error('court_name')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="case_no">Case number</label>
                            <input type="text" id="case_no" name="case_no" class="input"
                                   value="{{ old('case_no') }}" maxlength="80">
                            @error('case_no')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="case_type">What kind of case?</label>
                        <select id="case_type" name="case_type" class="select">
                            @foreach (['CIVIL_SUIT' => 'Civil suit', 'WRIT_PETITION' => 'Writ petition',
                                       'APPEAL' => 'Appeal', 'REVISION' => 'Revision',
                                       'EXECUTION' => 'Execution', 'CONTEMPT' => 'Contempt',
                                       'DIRECTION_CASE' => 'Direction case', 'OTHER' => 'Something else'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('case_type') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field flex flex-wrap gap-4">
                        <label class="font-medium">
                            <input type="checkbox" name="has_restraining_order" value="1"
                                   @checked(old('has_restraining_order'))>
                            There is a stay or restraining order
                        </label>
                        <label class="font-medium">
                            <input type="checkbox" name="is_direction_case" value="1"
                                   @checked(old('is_direction_case'))>
                            The court has given a direction
                        </label>
                    </div>

                    <div class="field">
                        <label for="case_remarks">Anything else about the case</label>
                        <textarea id="case_remarks" name="case_remarks" class="textarea"
                                  maxlength="2000">{{ old('case_remarks') }}</textarea>
                    </div>
                </div>

                <div class="wizard-actions">
                    <a href="{{ route('apply.evidence', $application) }}" class="btn btn-ghost">Back</a>
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
    document.querySelectorAll('input[data-toggle]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var block = document.getElementById(radio.dataset.toggle);
            if (block) block.hidden = radio.dataset.show !== '1';
        });
        if (radio.checked) radio.dispatchEvent(new Event('change'));
    });
})();
</script>
@endpush
