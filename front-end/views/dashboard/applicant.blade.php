@extends('layouts.app')

@section('title', 'Home')
@section('heading', 'Home')

@section('content')

@php $first = explode(' ', trim(auth()->user()->name))[0]; @endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ $first }}</h1>
    <p class="lede">
        Regularization of Possession &mdash; Evacuee Trust Property Board
    </p>
</div>

<div class="soft-panel">
    <p><strong>This page shows what needs your attention.</strong> If something is missing, unpaid, or waiting for you, you will see it first. If nothing is needed from you, your case is already with the department.</p>
</div>

{{-- ---------- Anything waiting on the applicant comes first ---------- --}}
@forelse ($actions as $action)
    <div class="alert alert-{{ $action['tone'] }}">
        @include('partials.icon', ['name' => 'alert'])
        <div class="flex flex-wrap items-center gap-3 w-full">
            <div class="min-w-0 flex-1">
                    <strong>{{ $action['title'] }}</strong>
                <p class="mb-0">
                    {{ $action['body'] }}
                    <span class="faint">({{ $action['app']->application_no }})</span>
                </p>
            </div>
            <a href="{{ $action['route'] }}" class="btn btn-primary btn-sm shrink-0">
                {{ $action['cta'] }} @include('partials.icon', ['name' => 'arrow-right'])
            </a>
        </div>
    </div>
@empty
    @if ($applications->isNotEmpty())
        <div class="alert alert-good">
            @include('partials.icon', ['name' => 'check'])
            <div>
                <p class="mb-0">
                    Nothing is waiting on you. Your application is with the department.
                </p>
            </div>
        </div>
    @endif
@endforelse

{{-- ---------- No application yet ---------- --}}
@if ($applications->isEmpty())
    <div class="card">
        <div class="card-body text-center py-10">
            <div class="brand-mark mx-auto mb-3">@include('partials.icon', ['name' => 'file'])</div>
            <h2 class="mb-2">You have not applied yet</h2>
            <p class="lede max-w-[540px] mx-auto mb-4">
                If you were in actual physical possession of an evacuee trust property
                <strong>prior to {{ $cutoffStated?->format('j F Y') }}</strong>, you can apply
                to be recorded as its tenant.
            </p>
            <a href="{{ route('apply.start') }}" class="btn btn-primary btn-lg">
                Start an application @include('partials.icon', ['name' => 'arrow-right'])
            </a>
            <p class="hint mt-3 mb-0">
                Six short steps &middot; you will need your CNIC, your papers, and a
                Rs. {{ number_format((float) $fee, 0) }} pay order
            </p>
        </div>
    </div>
@else

    {{-- ---------- Their applications ---------- --}}
    @foreach ($applications as $app)
        @php
            $steps = [
                ['Filed',            $app->submitted_at !== null],
                ['Deposit confirmed', $app->payment_status === 'PAID'],
                ['Examined',         $app->rent_fixed_at !== null || in_array($app->status, ['SITE_INSPECTION','ASSESSMENT_PROPOSED','NOTICE_ISSUED','OBJECTION_WINDOW','HEARING'], true)],
                ['Rent fixed',       $app->rent_fixed_at !== null],
                ['Approved',         $app->approved_at !== null],
                ['Regularized',      $app->regularized_at !== null],
            ];
            $doneCount = collect($steps)->filter(fn ($s) => $s[1])->count();
        @endphp

        <div class="card">
            <div class="card-head flex-wrap">
                <h3>{{ $app->application_no }}</h3>
                <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                    Payment {{ $app->payment_status }}
                </span>
                <span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span>
            </div>

            <div class="card-body">
                <dl class="kv">
                    <dt>Property</dt>
                    <dd>
                        {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                        &middot; {{ $app->district?->name }}
                    </dd>
                    @if ($app->assessed_monthly_rent)
                        <dt>Rent fixed</dt>
                        <dd><strong>Rs. {{ number_format((float) $app->assessed_monthly_rent, 2) }}</strong> a month</dd>
                    @endif
                    @if ((float) $app->total_arrears > 0)
                        <dt>Arrears</dt>
                        <dd>
                            Rs. {{ number_format((float) $app->arrears_balance, 2) }} still to pay
                            <span class="faint">of Rs. {{ number_format((float) $app->total_arrears, 2) }}</span>
                        </dd>
                    @endif
                </dl>

                {{-- Progress, in words the applicant understands --}}
                <hr class="divider">
                <div class="flex items-center gap-2 mb-2">
                    <strong class="text-[.88rem]">Progress</strong>
                    <span class="faint text-[.8rem]">{{ $doneCount }} of {{ count($steps) }} stages</span>
                </div>
                <div class="sla-bar mb-3">
                    <div class="sla-fill" style="width:{{ round($doneCount / count($steps) * 100) }}%"></div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($steps as [$label, $done])
                        <span class="badge badge-{{ $done ? 'good' : 'neutral' }}">
                            {{ $done ? '✓' : '' }} {{ $label }}
                        </span>
                    @endforeach
                </div>

                <div class="btn-row mt-4">
                    @if ($app->status === \App\Services\WorkflowService::DRAFT)
                        <a href="{{ route('apply.evidence', $app) }}" class="btn btn-primary btn-sm">
                            Continue this application
                        </a>
                    @else
                        <a href="{{ route('applications.show', $app) }}" class="btn btn-outline btn-sm">
                            View details
                        </a>
                    @endif
                    <a href="{{ route('documents.index', $app) }}" class="btn btn-ghost btn-sm">Documents</a>
                    <a href="{{ route('fee.index', $app) }}" class="btn btn-ghost btn-sm">Deposit</a>
                    @if ((float) $app->total_arrears > 0)
                        <a href="{{ route('arrears.index', $app) }}" class="btn btn-ghost btn-sm">Arrears</a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="btn-row">
        <a href="{{ route('apply.start') }}" class="btn btn-outline">
            @include('partials.icon', ['name' => 'plus']) Apply for another property
        </a>
    </div>
@endif

@endsection
