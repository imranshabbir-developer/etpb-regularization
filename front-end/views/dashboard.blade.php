@extends('layouts.app')

@section('title', __('Dashboard'))
@section('heading', __('Dashboard'))

@section('content')

    <div class="page-head">
        <h1>{{ now()->hour < 12 ? __('Good morning') : (now()->hour < 17 ? __('Good afternoon') : __('Good evening')) }},
            {{ auth()->user()->greetingName() }}</h1>
        <p class="lede">
            {{ __('Regularization of possession under Clause 3(ii) of the Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977.') }}
        </p>
    </div>

    <div class="tiles">
        @foreach ($tiles as $tile)
            <div class="tile {{ $tile['tone'] }}">
                <div class="tile-label">{{ __($tile['label']) }}</div>
                <div class="tile-value">{{ $tile['value'] }}</div>
                <div class="tile-sub">{{ __($tile['sub'] ?? '') }}</div>
            </div>
        @endforeach
    </div>

    @if ($breaches->isNotEmpty())
        <div class="card">
            <div class="card-head">
                <h3>{{ __('Statutory deadlines breached') }}</h3>
                <span class="badge badge-danger">{{ $breaches->count() }}</span>
                <div class="card-actions">
                    <span class="clause">{{ __('Clause 10(i)(e)') }}</span>
                    <span class="clause">{{ __('Clause 3(ii)(d)') }}</span>
                </div>
            </div>
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>{{ __('Application') }}</th>
                        <th>{{ __('Applicant') }}</th>
                        <th>{{ __('Stage') }}</th>
                        <th>{{ __('Deadline') }}</th>
                        <th>{{ __('Officer answerable') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($breaches as $app)
                        @php
                            $isApproval = $app->status === \App\Services\WorkflowService::PENDING_ADMIN_APPROVAL;
                            $sla = $isApproval
                                ? $app->adminApprovalSla()
                                : $app->assessmentSla();
                            $officer = $isApproval
                                ? $app->administrator?->name
                                : $app->districtOfficer?->name;
                            $days = (int) ($sla['days_left'] ?? 0);
                            if (! $sla['applies']) {
                                $slaText = __($sla['label']);
                            } elseif ($isApproval) {
                                $slaText = $days < 0
                                    ? __('Approval overdue by :days day(s)', ['days' => abs($days)])
                                    : __('Approval due in :days day(s)', ['days' => $days]);
                            } else {
                                $slaText = $days < 0
                                    ? __('Assessment overdue by :days day(s)', ['days' => abs($days)])
                                    : __('Assessment due in :days day(s)', ['days' => $days]);
                            }
                        @endphp
                        <tr>
                            <td class="nowrap">
                                <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                            </td>
                            <td>{{ $app->applicant?->full_name }}</td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                            <td class="nowrap">
                                <span class="badge badge-danger">{{ $slaText }}</span>
                            </td>
                            <td class="{{ $officer ? '' : 'row-muted' }}">{{ __($officer ?? 'Not assigned') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-head">
            <h3>{{ __('Recent activity') }}</h3>
            <div class="card-actions">
                <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">{{ __('View all') }}</a>
                @can('do', 'applications.create')
                    <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                        @include('partials.icon', ['name' => 'plus']) {{ __('New application') }}
                    </a>
                @endcan
            </div>
        </div>

        @if ($recent->isEmpty())
            <div class="empty">
                @include('partials.icon', ['name' => 'empty'])
                <p class="mb-0">{{ __('No applications yet.') }}</p>
                @if (auth()->user()->hasPermission('applications.create'))
                    <p class="mt-1">
                        <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                            {{ __('Create the first application') }}
                        </a>
                    </p>
                @endif
            </div>
        @else
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>{{ __('Application') }}</th>
                        <th>{{ __('Applicant') }}</th>
                        <th>{{ __('Property') }}</th>
                        <th>{{ __('District') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="num">{{ __('Arrears balance') }}</th>
                        <th>{{ __('Updated') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($recent as $app)
                        <tr>
                            <td class="nowrap">
                                <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                            </td>
                            <td>{{ $app->applicant?->full_name ?? '—' }}</td>
                            <td class="nowrap">
                                {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                            </td>
                            <td>{{ __($app->district?->name ?? '') }}</td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ __($app->statusLabel()) }}</span></td>
                            <td class="num">
                                @if ((float) $app->arrears_balance > 0)
                                    Rs. {{ number_format((float) $app->arrears_balance, 0) }}
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
        @endif
    </div>

    @if (! auth()->user()->isApplicant() && $byStatus->isNotEmpty())
        <div class="card">
            <div class="card-head"><h3>{{ __('Caseload by stage') }}</h3></div>
            <div class="card-body">
                <div class="inline-list">
                    @foreach ($byStatus as $status => $count)
                        <span class="badge badge-{{ \App\Services\WorkflowService::TONES[$status] ?? 'neutral' }}">
                            {{ __($labels[$status] ?? $status) }}
                            <strong>{{ $count }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

@endsection
