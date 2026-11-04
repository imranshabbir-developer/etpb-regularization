@extends('layouts.app')

@section('title', __('Applications'))
@section('heading', __('Applications'))

@section('content')

    <div class="page-head">
        <h1>{{ __('Applications') }}</h1>
        <p class="lede">{{ __('Regularization of possession — Clause 3(ii), Scheme 1977.') }}</p>
    </div>

    <div class="tiles">
        <div class="tile">
            <div class="tile-label">{{ __('Open cases') }}</div>
            <div class="tile-value">{{ number_format($stats['open'] ?? 0) }}</div>
            <div class="tile-sub">{{ __('Not yet regularized or rejected') }}</div>
        </div>
        <div class="tile {{ ($stats['fee_pending'] ?? 0) > 0 ? 'is-warn' : '' }}">
            <div class="tile-label">{{ __('Fee pending') }}</div>
            <div class="tile-value">{{ number_format($stats['fee_pending'] ?? 0) }}</div>
            <div class="tile-sub">{{ __('Deposit not confirmed') }}</div>
        </div>
        <div class="tile is-gold">
            <div class="tile-label">{{ __('Regularized') }}</div>
            <div class="tile-value">{{ number_format($stats['regularized'] ?? 0) }}</div>
            <div class="tile-sub">{{ __('Tenancy recorded') }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>{{ __('Filter') }}</h3>
            <div class="card-actions">
                @can('do', 'applications.create')
                    <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                        @include('partials.icon', ['name' => 'plus']) {{ __('New application') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body tight">
            <form method="GET" action="{{ route('applications.index') }}">
                <div class="grid-4">
                    <div class="field">
                        <label for="q">{{ __('Search') }}</label>
                        <input type="search" id="q" name="q" class="input"
                               value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ __('Application no., applicant, CNIC, property no.') }}">
                    </div>
                    <div class="field">
                        <label for="status">{{ __('Stage') }}</label>
                        <select id="status" name="status" class="select">
                            <option value="">{{ __('All stages') }}</option>
                            @foreach ($statuses as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="district">{{ __('District') }}</label>
                        <select id="district" name="district" class="select">
                            <option value="">{{ __('All districts') }}</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected((int) ($filters['district'] ?? 0) === $d->id)>
                                    {{ __($d->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="display:flex;align-items:flex-end;gap:.5rem">
                        <button class="btn btn-primary" type="submit">{{ __('Apply') }}</button>
                        <a class="btn btn-ghost" href="{{ route('applications.index') }}">{{ __('Reset') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>{{ __(':count application(s)', ['count' => number_format($applications->total())]) }}</h3>
        </div>

        @if ($applications->isEmpty())
            <div class="empty">
                @include('partials.icon', ['name' => 'empty'])
                <p class="mb-0">{{ __('No applications match these filters.') }}</p>
            </div>
        @else
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>{{ __('Application no.') }}</th>
                        <th>{{ __('Applicant') }}</th>
                        <th>{{ __('CNIC') }}</th>
                        <th>{{ __('Property') }}</th>
                        <th>{{ __('District') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th>{{ __('Stage') }}</th>
                        <th class="num">{{ __('Assessed rent') }}</th>
                        <th class="num">{{ __('Arrears balance') }}</th>
                        <th>{{ __('Updated') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($applications as $app)
                        <tr>
                            <td class="nowrap">
                                <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                                @if ($app->is_sub_judice)
                                    <span class="badge badge-warn" title="{{ __('Pending before a court or stayed') }}">{{ __('Sub judice') }}</span>
                                @endif
                            </td>
                            <td>
                                {{ $app->applicant?->full_name }}
                                <div class="faint" style="font-size:.78rem">
                                    {{ $app->applicant?->parentage_type === 'HUSBAND' ? __('w/o') : __('s/o') }}
                                    {{ $app->applicant?->parentage_name }}
                                </div>
                            </td>
                            <td class="nowrap num">{{ $app->applicant?->maskedCnic() }}</td>
                            <td class="nowrap">
                                {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                            </td>
                            <td>{{ __($app->district?->name ?? '') }}</td>
                            <td>
                                <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                                    {{ __($app->payment_status ?? '') }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                            <td class="num">
                                @if ($app->assessed_monthly_rent)
                                    Rs. {{ number_format((float) $app->assessed_monthly_rent, 0) }}
                                @else
                                    <span class="faint">—</span>
                                @endif
                            </td>
                            <td class="num">
                                @if ((float) $app->arrears_balance > 0)
                                    <strong>Rs. {{ number_format((float) $app->arrears_balance, 0) }}</strong>
                                @else
                                    <span class="faint">—</span>
                                @endif
                            </td>
                            <td class="nowrap faint">{{ $app->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($applications->hasPages())
                <div class="card-foot">{{ $applications->links() }}</div>
            @endif
        @endif
    </div>

@endsection
