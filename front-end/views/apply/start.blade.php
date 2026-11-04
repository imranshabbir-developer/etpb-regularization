@extends('layouts.app')

@section('title', __('Apply'))
@section('heading', __('Apply for regularization'))

@section('content')

<div class="container-narrow">

    <div class="page-head">
        <h1>{{ __('Apply to have your possession regularized') }}</h1>
        <p class="lede">
            {!! __('If you were in actual physical possession of an evacuee trust property <strong>prior to :date</strong>, you may apply to be recorded as its tenant.', ['date' => e($cutoffStated->translatedFormat('j F Y'))]) !!}
        </p>
    </div>

    <div class="soft-panel">
        <p>
            <strong>{{ __('Start with the basics.') }}</strong>
            {{ __('You only need about 8–10 essential particulars (who you are, where the property is, when possession began, geo pin, and biometric). Leave anything you do not know blank — office staff can sit with you later and complete the rest on the one-window desk. Progress is saved between steps.') }}
        </p>
    </div>

    @if ($inProgress->isNotEmpty())
        <div class="card">
            <div class="card-head"><h3>{{ __('You have an application in progress') }}</h3></div>
            <div class="card-body">
                @foreach ($inProgress as $app)
                    <div class="flex flex-wrap items-center gap-3 justify-between py-1">
                        <div>
                            <strong>{{ $app->application_no }}</strong>
                            <div class="faint text-[.8rem]">
                                {{ $app->property?->property_no }} &middot; {{ __($app->district?->name ?? '') }}
                                &middot; {{ __('started :time', ['time' => $app->created_at->diffForHumans()]) }}
                            </div>
                        </div>
                        <a href="{{ route('apply.evidence', $app) }}" class="btn btn-primary btn-sm">
                            {{ __('Continue') }} @include('partials.icon', ['name' => 'arrow-right'])
                        </a>
                    </div>
                    @if (! $loop->last)<hr class="divider">@endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-head"><h3>{{ __('Before you start') }}</h3></div>
        <div class="card-body">

            <h4>{{ __('You can apply if') }}</h4>
            <ul class="list-disc ps-5 mb-4 text-[.92rem]">
                <li>{!! __('you were in <strong>actual physical possession prior to :date</strong>, and', ['date' => e($cutoffStated->translatedFormat('j F Y'))]) !!}</li>
                <li>{{ __('you can show that possession with documents, or with a court order.') }}</li>
            </ul>

            <div class="alert alert-info">
                @include('partials.icon', ['name' => 'info'])
                <div>
                    <p class="mb-0">
                        {!! __('If you qualify you become a <strong>recorded tenant</strong> of the Board and rent is fixed for the property. Rent is also payable for the period you have already been in possession. This scheme does not by itself make you the owner.') !!}
                    </p>
                </div>
            </div>

            <h4>{{ __('What you will need') }}</h4>
            <ul class="list-disc ps-5 mb-4 text-[.92rem]">
                <li>{!! __('Your <strong>CNIC</strong>') !!}</li>
                <li>{{ __('The property number, its address, and its area') }}</li>
                <li>{{ __('The date your possession began') }}</li>
                <li>{{ __('Certified copies of your evidence:') }}
                    <span class="faint">{{ __(':list and others', ['list' => $docTypes->pluck('name')->take(6)->map(fn ($n) => __($n))->implode(', ')]) }}</span></li>
                <li>{!! __('A <strong>Rs. :amount</strong> pay order, banker’s cheque or demand draft in favour of <strong>Chairman ETPB</strong>', ['amount' => e(number_format((float) $fee, 0))]) !!}</li>
            </ul>

            <div class="alert alert-warn">
                @include('partials.icon', ['name' => 'alert'])
                <div>
                    <p class="mb-0">
                        {!! __('Your application is <strong>not processed</strong> until the Rs. :amount deposit has been confirmed with the bank. Until then its status stays <strong>pending</strong>.', ['amount' => e(number_format((float) $fee, 0))]) !!}
                    </p>
                </div>
            </div>

            <h4>{{ __('How long it takes') }}</h4>
            <p class="muted text-[.92rem] mb-4">
                {!! __('Once your deposit is confirmed, the District Officer has <strong>60 days</strong> to assess the rent and the Administrator has <strong>one month</strong> to approve. Objections, hearings or a court case will add to that. You can follow every step here.') !!}
            </p>

            <div class="btn-row">
                <a href="{{ route('apply.applicant') }}" class="btn btn-primary btn-lg">
                    {{ __('Start the application') }} @include('partials.icon', ['name' => 'arrow-right'])
                </a>
                <a href="{{ route('apply.mine') }}" class="btn btn-ghost">{{ __('My applications') }}</a>
            </div>

            <p class="hint mt-2 mb-0">
                {!! __('Six short steps. Your answers are saved as you go, and you can come back later. Not sure about something? Use <strong>Ask about the scheme</strong> at the bottom of the screen.') !!}
            </p>
        </div>
    </div>

</div>

@endsection
