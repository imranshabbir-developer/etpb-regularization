@extends('layouts.app')

@section('title', 'Application submitted')
@section('heading', 'Application submitted')

@section('content')

<div class="container-narrow">
    <div class="card">
        <div class="card-body text-center py-10">
            <div class="brand-mark mx-auto mb-3">@include('partials.icon', ['name' => 'check'])</div>
            <h1 class="mb-1">Your application has been submitted</h1>
            <p class="lede mb-4">Keep this number safe — quote it whenever you contact the office.</p>

            <p class="text-2xl font-bold text-pk-800 tabular-nums mb-5">
                {{ $application->application_no }}
            </p>

            <div class="inline-list justify-center mb-5">
                <span class="badge badge-{{ $application->payment_status === 'PAID' ? 'good' : 'warn' }}">
                    Payment {{ $application->payment_status }}
                </span>
                <span class="badge badge-{{ $application->statusTone() }}">{{ $application->statusLabel() }}</span>
            </div>

            @if ($application->payment_status !== 'PAID')
                <div class="alert alert-warn text-start">
                    @include('partials.icon', ['name' => 'alert'])
                    <div>
                        <p class="mb-0">
                            <strong>Your Rs. {{ number_format((float) $fee, 0) }} deposit has not yet been
                            confirmed.</strong> The department will not begin processing until Accounts
                            confirms it with the bank. If you have not yet taken the instrument to your
                            district office, please do so.
                        </p>
                    </div>
                </div>
            @endif

            <div class="text-start">
                <h4>What happens next</h4>
                <ol class="ps-5 text-[.92rem] muted">
                    <li>Accounts confirms your deposit and your status becomes <strong>PAID</strong>.</li>
                    <li>The District Officer examines your papers and inspects the property.</li>
                    <li>He proposes a rent and issues a public notice; 15 days are allowed for objections.</li>
                    <li>He fixes the rent giving reasons, and the arrears are worked out.</li>
                    <li>You clear the arrears, or an instalment plan is approved.</li>
                    <li>The Administrator approves, and a tenancy agreement is executed.</li>
                </ol>
                <p class="hint">
                    You can follow every step here. We will not ask you for money except through
                    the official instrument in favour of Chairman ETPB.
                </p>
            </div>

            <div class="btn-row justify-center mt-4">
                <a href="{{ route('apply.mine') }}" class="btn btn-primary">My applications</a>
                <a href="{{ route('applications.show', $application) }}" class="btn btn-outline">
                    View this application
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
