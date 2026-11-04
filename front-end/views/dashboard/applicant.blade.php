@extends('layouts.app')

@section('title', __('Home'))
@section('heading', __('Home'))

@section('content')

@php $first = auth()->user()->greetingName(); @endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? __('Good morning') : (now()->hour < 17 ? __('Good afternoon') : __('Good evening')) }},
        {{ $first }}</h1>
    <p class="lede">
        {{ __('Regularization of Possession — Evacuee Trust Property Board') }}
    </p>
</div>

@if ($applications->isNotEmpty())
    @php
        $regularizedCount = $applications->where('status', \App\Services\WorkflowService::REGULARIZED)->count();
        $openCount = $applications->count() - $regularizedCount;
    @endphp
    <div class="tiles">
        <div class="tile">
            <div class="tile-label">{{ __('My applications') }}</div>
            <div class="tile-value">{{ number_format($applications->count()) }}</div>
            <div class="tile-sub">{{ __(':open open · :regularized regularized', ['open' => $openCount, 'regularized' => $regularizedCount]) }}</div>
        </div>
        <div class="tile {{ count($actions) > 0 ? 'is-warn' : '' }}">
            <div class="tile-label">{{ __('Needs your action') }}</div>
            <div class="tile-value">{{ number_format(count($actions)) }}</div>
            <div class="tile-sub">{{ count($actions) ? __('Finish these first') : __('Nothing waiting on you') }}</div>
        </div>
        <div class="tile is-gold">
            <div class="tile-label">{{ __('Regularized') }}</div>
            <div class="tile-value">{{ number_format($regularizedCount) }}</div>
            <div class="tile-sub">{{ __('Tenancy recorded') }}</div>
        </div>
    </div>
@endif

{{-- ---------- Anything waiting on the applicant comes first ---------- --}}
@forelse ($actions as $action)
    <div class="alert alert-{{ $action['tone'] }}">
        @include('partials.icon', ['name' => 'alert'])
        <div class="flex flex-wrap items-center gap-3 w-full">
            <div class="min-w-0 flex-1">
                    <strong>{{ __($action['title']) }}</strong>
                <p class="mb-0">
                    {{ __($action['body'] ?? '') }}
                    <span class="faint">({{ $action['app']->application_no }})</span>
                </p>
            </div>
            <a href="{{ $action['route'] }}" class="btn btn-primary btn-sm shrink-0">
                {{ __($action['cta']) }} @include('partials.icon', ['name' => 'arrow-right'])
            </a>
        </div>
    </div>
@empty
    @if ($applications->isNotEmpty())
        <div class="alert alert-good">
            @include('partials.icon', ['name' => 'check'])
            <div>
                <p class="mb-0">
                    {{ __('Nothing is waiting on you. Your application is with the department.') }}
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
            <h2 class="mb-2">{{ __('You have not applied yet') }}</h2>
            <p class="lede max-w-[540px] mx-auto mb-4">
                {!! __('If you were in actual physical possession of an evacuee trust property <strong>prior to :date</strong>, you can apply to be recorded as its tenant.', ['date' => e($cutoffStated?->translatedFormat('j F Y'))]) !!}
            </p>
            <a href="{{ route('apply.start') }}" class="btn btn-primary btn-lg">
                {{ __('Start an application') }} @include('partials.icon', ['name' => 'arrow-right'])
            </a>
            <p class="hint mt-3 mb-0">
                {{ __('Six short steps · you will need your CNIC, your papers, and a Rs. :fee pay order', ['fee' => number_format((float) $fee, 0)]) }}
            </p>
        </div>
    </div>
@else

    {{-- ---------- Their applications ---------- --}}
    @foreach ($applications as $app)
        @php
            $steps = [
                [__('Filed'),             $app->submitted_at !== null],
                [__('Deposit confirmed'), $app->payment_status === 'PAID'],
                [__('Examined'),          $app->rent_fixed_at !== null || in_array($app->status, ['SITE_INSPECTION','ASSESSMENT_PROPOSED','NOTICE_ISSUED','OBJECTION_WINDOW','HEARING'], true)],
                [__('Rent fixed'),        $app->rent_fixed_at !== null],
                [__('Approved'),          $app->approved_at !== null],
                [__('Regularized'),       $app->regularized_at !== null],
            ];
            $doneCount = collect($steps)->filter(fn ($s) => $s[1])->count();
        @endphp

        <div class="card">
            <div class="card-head flex-wrap">
                <h3>{{ $app->application_no }}</h3>
                <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                    {{ __('Payment :status', ['status' => __($app->payment_status ?? '')]) }}
                </span>
                <span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span>
            </div>

            <div class="card-body">
                <dl class="kv">
                    <dt>{{ __('Property') }}</dt>
                    <dd>
                        {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                        &middot; {{ __($app->district?->name ?? '') }}
                    </dd>
                    @if ($app->assessed_monthly_rent)
                        <dt>{{ __('Rent fixed') }}</dt>
                        <dd><strong>Rs. {{ number_format((float) $app->assessed_monthly_rent, 2) }}</strong> {{ __('a month') }}</dd>
                    @endif
                    @if ((float) $app->total_arrears > 0)
                        <dt>{{ __('Arrears') }}</dt>
                        <dd>
                            {{ __(':amount still to pay', ['amount' => 'Rs. ' . number_format((float) $app->arrears_balance, 2)]) }}
                            <span class="faint">{{ __('of :amount', ['amount' => 'Rs. ' . number_format((float) $app->total_arrears, 2)]) }}</span>
                        </dd>
                    @endif
                </dl>

                <hr class="divider">
                <div class="flex items-center gap-2 mb-2">
                    <strong class="text-[.88rem]">{{ __('Progress') }}</strong>
                    <span class="faint text-[.8rem]">{{ __(':done of :total', ['done' => $doneCount, 'total' => count($steps)]) }}</span>
                </div>
                <div class="sla-bar mb-1">
                    <div class="sla-fill" style="width:{{ round($doneCount / max(count($steps), 1) * 100) }}%"></div>
                </div>

                <div class="btn-row mt-4">
                    @if ($app->status === \App\Services\WorkflowService::DRAFT)
                        <a href="{{ route('apply.evidence', $app) }}" class="btn btn-primary btn-sm">
                            {{ __('Continue') }}
                        </a>
                    @else
                        <a href="{{ route('applications.show', $app) }}" class="btn btn-outline btn-sm">
                            {{ __('View') }}
                        </a>
                    @endif
                    <a href="{{ route('documents.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Documents') }}</a>
                    <a href="{{ route('fee.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Deposit') }}</a>
                    @if ((float) $app->total_arrears > 0)
                        <a href="{{ route('arrears.index', $app) }}" class="btn btn-ghost btn-sm">{{ __('Arrears') }}</a>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div class="btn-row">
        <a href="{{ route('apply.start') }}" class="btn btn-outline">
            @include('partials.icon', ['name' => 'plus']) {{ __('Apply for another property') }}
        </a>
    </div>
@endif

@endsection
