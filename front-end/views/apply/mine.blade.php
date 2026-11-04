@extends('layouts.app')

@section('title', __('My applications'))
@section('heading', __('My applications'))

@section('content')

<div class="page-head">
    <h1>{{ __('My applications') }}</h1>
    <p class="lede">{{ __('Everything you have filed, and where each one stands.') }}</p>
</div>

@if ($applications->isEmpty())
    <div class="card">
        <div class="empty">
            @include('partials.icon', ['name' => 'empty'])
            <p class="mb-0">{{ __('You have not filed an application yet.') }}</p>
            <p class="mt-2">
                <a href="{{ route('apply.start') }}" class="btn btn-primary">{{ __('Start an application') }}</a>
            </p>
        </div>
    </div>
@else
    <div class="btn-row mb-4">
        <a href="{{ route('apply.start') }}" class="btn btn-primary">
            @include('partials.icon', ['name' => 'plus']) {{ __('Start another application') }}
        </a>
    </div>

    @foreach ($applications as $app)
        @php $draft = $app->status === \App\Services\WorkflowService::DRAFT; @endphp
        <div class="card">
            <div class="card-head flex-wrap">
                <h3>{{ $app->application_no }}</h3>
                <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                    {{ __('Payment :status', ['status' => __($app->payment_status ?? '')]) }}
                </span>
                <span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span>
                @if ($app->is_sub_judice)
                    <span class="badge badge-danger">{{ __('Court case pending') }}</span>
                @endif
            </div>
            <div class="card-body">
                <dl class="kv">
                    <dt>{{ __('Property') }}</dt>
                    <dd>
                        {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                        &middot; {{ __($app->district?->name ?? '') }}
                    </dd>
                    <dt>{{ __('Filed') }}</dt>
                    <dd>{{ $app->created_at->translatedFormat('d F Y') }}</dd>
                    @if ($app->possession)
                        <dt>{{ __('Possession since') }}</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($app->possession->date_of_possession)->translatedFormat('d F Y') }}</dd>
                    @endif
                    @if ($app->assessed_monthly_rent)
                        <dt>{{ __('Rent fixed') }}</dt>
                        <dd><strong>Rs. {{ number_format((float) $app->assessed_monthly_rent, 2) }}</strong> {{ __('a month') }}</dd>
                    @endif
                    @if ((float) $app->total_arrears > 0)
                        <dt>{{ __('Arrears assessed') }}</dt>
                        <dd>Rs. {{ number_format((float) $app->total_arrears, 2) }}</dd>
                        <dt>{{ __('Still to pay') }}</dt>
                        <dd><strong>Rs. {{ number_format((float) $app->arrears_balance, 2) }}</strong></dd>
                    @endif
                </dl>

                @if ($draft)
                    <div class="alert alert-warn mt-2">
                        @include('partials.icon', ['name' => 'alert'])
                        <div>
                            <p class="mb-0">
                                {!! __('This application has <strong>not been submitted yet</strong>.') !!}
                                {{ __('Finish it so the department can begin.') }}
                            </p>
                        </div>
                    </div>
                @elseif ($app->payment_status !== 'PAID')
                    <div class="alert alert-warn mt-2">
                        @include('partials.icon', ['name' => 'alert'])
                        <div>
                            <p class="mb-0">
                                {!! __('Waiting for your <strong>Rs. 5,000 deposit</strong> to be confirmed.') !!}
                                {{ __('Nothing moves until then.') }}
                            </p>
                        </div>
                    </div>
                @elseif ($app->status === \App\Services\WorkflowService::RETURNED_DEFICIENT)
                    <div class="alert alert-danger mt-2">
                        @include('partials.icon', ['name' => 'alert'])
                        <div>
                            <p class="mb-0">
                                <strong>{{ __('The office needs something from you.') }}</strong>
                                {{ $app->status_remarks }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="btn-row mt-3">
                    @if ($draft)
                        <a href="{{ route('apply.evidence', $app) }}" class="btn btn-primary btn-sm">
                            {{ __('Continue this application') }} @include('partials.icon', ['name' => 'arrow-right'])
                        </a>
                    @else
                        <a href="{{ route('applications.show', $app) }}" class="btn btn-outline btn-sm">
                            {{ __('View details') }}
                        </a>
                    @endif
                    <a href="{{ route('documents.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Documents') }}</a>
                    <a href="{{ route('fee.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Deposit') }}</a>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection
