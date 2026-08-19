@extends('layouts.app')

@section('title', 'Apply')
@section('heading', 'Apply for regularization')

@section('content')

<div class="container-narrow">

    <div class="page-head">
        <h1>Apply to have your possession regularized</h1>
        <p class="lede">
            If you have held an evacuee trust property since before
            <strong>{{ $cutoff->addDay()->format('j F Y') }}</strong>, you may apply to be
            recorded as its tenant.
        </p>
    </div>

    @if ($inProgress->isNotEmpty())
        <div class="card">
            <div class="card-head"><h3>You have an application in progress</h3></div>
            <div class="card-body">
                @foreach ($inProgress as $app)
                    <div class="flex flex-wrap items-center gap-3 justify-between py-1">
                        <div>
                            <strong>{{ $app->application_no }}</strong>
                            <div class="faint text-[.8rem]">
                                {{ $app->property?->property_no }} &middot; {{ $app->district?->name }}
                                &middot; started {{ $app->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <a href="{{ route('apply.evidence', $app) }}" class="btn btn-primary btn-sm">
                            Continue @include('partials.icon', ['name' => 'arrow-right'])
                        </a>
                    </div>
                    @if (! $loop->last)<hr class="divider">@endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-head"><h3>Before you start</h3></div>
        <div class="card-body">

            <h4>You can apply if</h4>
            <ul class="list-disc ps-5 mb-4 text-[.92rem]">
                <li>you were in <strong>actual physical possession before
                    {{ $cutoff->addDay()->format('j F Y') }}</strong>, and</li>
                <li>you can show that possession with documents, or with a court order.</li>
            </ul>

            <div class="alert alert-info">
                @include('partials.icon', ['name' => 'info'])
                <div>
                    <p class="mb-0">
                        If you qualify you become a <strong>recorded tenant</strong> of the Board and
                        rent is fixed for the property. Rent is also payable for the period you have
                        already been in possession. This scheme does not by itself make you the owner.
                    </p>
                </div>
            </div>

            <h4>What you will need</h4>
            <ul class="list-disc ps-5 mb-4 text-[.92rem]">
                <li>Your <strong>CNIC</strong></li>
                <li>The property number, its address, and its area</li>
                <li>The date your possession began</li>
                <li>Certified copies of your evidence:
                    <span class="faint">{{ $docTypes->pluck('name')->take(6)->implode(', ') }}
                    and others</span></li>
                <li>A <strong>Rs. {{ number_format((float) $fee, 0) }}</strong> pay order,
                    banker&rsquo;s cheque or demand draft in favour of <strong>Chairman ETPB</strong></li>
            </ul>

            <div class="alert alert-warn">
                @include('partials.icon', ['name' => 'alert'])
                <div>
                    <p class="mb-0">
                        Your application is <strong>not processed</strong> until the
                        Rs. {{ number_format((float) $fee, 0) }} deposit has been confirmed with the
                        bank. Until then its status stays <strong>pending</strong>.
                    </p>
                </div>
            </div>

            <h4>How long it takes</h4>
            <p class="muted text-[.92rem] mb-4">
                Once your deposit is confirmed, the District Officer has <strong>60 days</strong> to
                assess the rent and the Administrator has <strong>one month</strong> to approve.
                Objections, hearings or a court case will add to that. You can follow every step here.
            </p>

            <div class="btn-row">
                <a href="{{ route('apply.applicant') }}" class="btn btn-primary btn-lg">
                    Start the application @include('partials.icon', ['name' => 'arrow-right'])
                </a>
                <a href="{{ route('apply.mine') }}" class="btn btn-ghost">My applications</a>
            </div>

            <p class="hint mt-2 mb-0">
                Six short steps. Your answers are saved as you go, and you can come back later.
                Not sure about something? Use <strong>Ask about the scheme</strong> at the bottom
                of the screen.
            </p>
        </div>
    </div>

</div>

@endsection
