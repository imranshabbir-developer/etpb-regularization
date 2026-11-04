@extends('layouts.app')

@section('title', __('Your possession'))
@section('heading', __('Step 3 of 6 — Your possession'))

@section('content')

<div class="container-narrow">
    @include('partials.wizard-steps')

    <div class="page-head">
        <h1>{{ __('Your possession') }}</h1>
        <p class="lede">
            {{ __('When possession began decides both whether you qualify and how far back rent is owed.') }}
        </p>
    </div>

    <div class="alert alert-warn">
        @include('partials.icon', ['name' => 'alert'])
        <div>
            <p class="mb-0">
                {!! __('Possession must have begun <strong>prior to :date</strong>.', ['date' => e($cutoffStated->translatedFormat('j F Y'))]) !!}
                <span class="clause">{{ __('Clause 3(ii)(a)') }}</span>
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('apply.possession.store') }}" novalidate>
        @csrf

        <div class="essentials-banner">
            <span aria-hidden="true">★</span>
            <div>
                <strong>{{ __('Required:') }}</strong> {{ __('date of possession, how you came to hold it, and the declaration.') }}
                {{ __('Court judgment details are optional on this step.') }}
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="grid-2">
                    <div class="field">
                        <label for="date_of_possession">
                            {{ __('When did your possession begin?') }} <span class="req">*</span>
                        </label>
                        <input type="date" id="date_of_possession" name="date_of_possession"
                               class="input @error('date_of_possession') is-invalid @enderror"
                               value="{{ old('date_of_possession') }}"
                               max="{{ $cutoff->toDateString() }}" required>
                        <p class="hint">{{ __('On or before :date.', ['date' => $cutoff->translatedFormat('d-m-Y')]) }}</p>
                        @error('date_of_possession')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="possession_nature">{{ __('How did you come to hold it?') }} <span class="req">*</span></label>
                        <select id="possession_nature" name="possession_nature" class="select">
                            @foreach (['SELF' => __('I took possession myself'),
                                       'INHERITED' => __('I inherited it'),
                                       'PURCHASED' => __('I bought it from someone'),
                                       'ALLOTTED' => __('It was allotted to me'),
                                       'OTHER' => __('Some other way')] as $v => $l)
                                <option value="{{ $v }}" @selected(old('possession_nature') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-info">
                    @include('partials.icon', ['name' => 'info'])
                    <div>
                        <p class="mb-0">
                            {!! __('Rent is counted from the earliest of <strong>1 July 2000</strong>, your possession date, or a court judgment date (if any).') !!}
                            <span class="clause">{{ __('Clause 3(ii)(b)') }}</span>
                        </p>
                    </div>
                </div>

                <div class="field flex items-start gap-2">
                    <input type="checkbox" id="declaration" name="declaration" value="1"
                           class="mt-1" required @checked(old('declaration'))>
                    <label for="declaration" class="m-0 font-medium text-[.9rem]">
                        {{ __('I declare that the information I have given is true.') }}
                        <span class="req">*</span>
                    </label>
                </div>
                @error('declaration')<p class="error-text">{{ $message }}</p>@enderror
            </div>
        </div>

        <details class="optional-panel">
            <summary>
                {{ __('Story of possession & court judgment') }}
                <span class="badge badge-neutral">{{ __('Optional — office can fill later') }}</span>
            </summary>
            <div class="optional-body">
                <div class="field">
                    <label for="possession_description">{{ __('Tell us briefly how it happened') }}</label>
                    <textarea id="possession_description" name="possession_description"
                              class="textarea" maxlength="2000">{{ old('possession_description') }}</textarea>
                </div>

                <fieldset class="group">
                    <legend>{{ __('Has a court ever ruled about this property?') }}</legend>
                    <p class="hint mt-0">
                        {{ __('If a judgment in your favour is earlier than possession, rent may run from the court’s date.') }}
                        <span class="clause">{{ __('Clause 3(ii)(b)') }}</span>
                    </p>
                    <div class="grid-2">
                        <div class="field">
                            <label for="date_of_judicial_verdict">{{ __('Date of the judgment') }}</label>
                            <input type="date" id="date_of_judicial_verdict" name="date_of_judicial_verdict"
                                   class="input" value="{{ old('date_of_judicial_verdict') }}"
                                   max="{{ now()->toDateString() }}">
                        </div>
                        <div class="field">
                            <label for="court_name">{{ __('Name of Court') }}</label>
                            <input type="text" id="court_name" name="court_name"
                                   class="input" value="{{ old('court_name') }}" maxlength="200">
                        </div>
                        <div class="field">
                            <label for="judicial_reference">{{ __('Court and case number') }}</label>
                            <input type="text" id="judicial_reference" name="judicial_reference"
                                   class="input" value="{{ old('judicial_reference') }}" maxlength="150">
                        </div>
                    </div>
                    <div class="field">
                        <label for="judgment_gist">{{ __('Gist of Decision / Judgement') }}</label>
                        <textarea id="judgment_gist" name="judgment_gist" class="textarea"
                                  maxlength="700" rows="3"
                                  placeholder="{{ __('Up to 100 words') }}">{{ old('judgment_gist') }}</textarea>
                        @error('judgment_gist')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                </fieldset>
            </div>
        </details>

        <div class="wizard-actions">
            <a href="{{ route('apply.property') }}" class="btn btn-ghost">{{ __('Back') }}</a>
            <span class="spacer"></span>
            <button type="submit" class="btn btn-primary btn-lg">
                {{ __('Save and continue') }} @include('partials.icon', ['name' => 'arrow-right'])
            </button>
        </div>
        <p class="hint mt-2 mb-0">
            {{ __('Your application will be created and given a number. Documents and deposit can follow.') }}
        </p>
    </form>
</div>

@endsection
