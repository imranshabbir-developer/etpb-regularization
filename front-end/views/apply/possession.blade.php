@extends('layouts.app')

@section('title', 'Your possession')
@section('heading', 'Step 3 of 6 — Your possession')

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>Your possession</h1>
        <p class="lede">
            When your possession began decides both whether you qualify and how far back
            rent is owed, so take care over the date.
        </p>
    </div>

    <div class="alert alert-warn">
        @include('partials.icon', ['name' => 'alert'])
        <div>
            <p class="mb-0">
                Possession must have begun <strong>prior to {{ $cutoffStated->format('j F Y') }}</strong>.
                A later date cannot be accepted &mdash; the cut-off is fixed by the Scheme itself.
                <span class="clause">Clause 3(ii)(a)</span>
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('apply.possession.store') }}" novalidate>
        @csrf

        <div class="card">
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="date_of_possession">
                            When did your possession begin? <span class="req">*</span>
                        </label>
                        <input type="date" id="date_of_possession" name="date_of_possession"
                               class="input @error('date_of_possession') is-invalid @enderror"
                               value="{{ old('date_of_possession') }}"
                               max="{{ $cutoff->toDateString() }}" required>
                        <p class="hint">On or before {{ $cutoff->format('d-m-Y') }}.</p>
                        @error('date_of_possession')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="possession_nature">How did you come to hold it? <span class="req">*</span></label>
                        <select id="possession_nature" name="possession_nature" class="select">
                            @foreach (['SELF' => 'I took possession myself',
                                       'INHERITED' => 'I inherited it',
                                       'PURCHASED' => 'I bought it from someone',
                                       'ALLOTTED' => 'It was allotted to me',
                                       'OTHER' => 'Some other way'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('possession_nature') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="possession_description">Tell us briefly how it happened</label>
                    <textarea id="possession_description" name="possession_description"
                              class="textarea" maxlength="2000">{{ old('possession_description') }}</textarea>
                    <p class="hint">
                        A few lines is enough. This helps the officer understand your case.
                    </p>
                </div>

                <fieldset class="group">
                    <legend>Has a court ever ruled about this property?</legend>
                    <p class="hint mt-0">
                        If a court gave a judgment or declaration in your favour and it is
                        <strong>earlier</strong> than your possession, rent is counted from the
                        court&rsquo;s date instead. <span class="clause">Clause 3(ii)(b)</span>
                    </p>
                    <div class="grid-2">
                        <div class="field">
                            <label for="date_of_judicial_verdict">Date of the judgment</label>
                            <input type="date" id="date_of_judicial_verdict" name="date_of_judicial_verdict"
                                   class="input" value="{{ old('date_of_judicial_verdict') }}"
                                   max="{{ now()->toDateString() }}">
                        </div>
                        <div class="field">
                            <label for="judicial_reference">Court and case number</label>
                            <input type="text" id="judicial_reference" name="judicial_reference"
                                   class="input" value="{{ old('judicial_reference') }}" maxlength="150">
                        </div>
                    </div>
                </fieldset>

                <div class="alert alert-info">
                    @include('partials.icon', ['name' => 'info'])
                    <div>
                        <p class="mb-0">
                            <strong>Rent will be owed from the earliest</strong> of 1 July 2000, the
                            date your possession began, or the date of a court judgment. If you have
                            held the property since the 1990s, that means rent from the 1990s.
                            The exact figure is worked out by the District Officer.
                        </p>
                    </div>
                </div>

                <div class="field flex items-start gap-2">
                    <input type="checkbox" id="declaration" name="declaration" value="1"
                           class="mt-1" required @checked(old('declaration'))>
                    <label for="declaration" class="m-0 font-medium text-[.9rem]">
                        I declare that the information I have given is true. I understand that a
                        false statement may lead to my application being rejected.
                        <span class="req">*</span>
                    </label>
                </div>
                @error('declaration')<p class="error-text">{{ $message }}</p>@enderror

                <div class="wizard-actions">
                    <a href="{{ route('apply.property') }}" class="btn btn-ghost">Back</a>
                    <span class="spacer"></span>
                    <button type="submit" class="btn btn-primary btn-lg">
                        Save and continue @include('partials.icon', ['name' => 'arrow-right'])
                    </button>
                </div>

                <p class="hint mt-2 mb-0">
                    Your application will be created and given a number. You can then attach your
                    documents and come back later if you need to.
                </p>
            </div>
        </div>
    </form>
</div>

@endsection
