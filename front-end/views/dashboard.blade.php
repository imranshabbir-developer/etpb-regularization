@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

    <div class="page-head">
        <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
            {{ explode(' ', auth()->user()->name)[0] }}</h1>
        <p class="lede">
            Regularization of possession under Clause 3(ii) of the Scheme for the Management and
            Disposal of Urban Evacuee Trust Properties, 1977.
        </p>
    </div>

    <div class="tiles">
        @foreach ($tiles as $tile)
            <div class="tile {{ $tile['tone'] }}">
                <div class="tile-label">{{ $tile['label'] }}</div>
                <div class="tile-value">{{ $tile['value'] }}</div>
                <div class="tile-sub">{{ $tile['sub'] }}</div>
            </div>
        @endforeach
    </div>

    @if ($breaches->isNotEmpty())
        <div class="card">
            <div class="card-head">
                <h3>Statutory deadlines breached</h3>
                <span class="badge badge-danger">{{ $breaches->count() }}</span>
                <div class="card-actions">
                    <span class="clause">Clause 10(i)(e)</span>
                    <span class="clause">Clause 3(ii)(d)</span>
                </div>
            </div>
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Application</th>
                        <th>Applicant</th>
                        <th>Stage</th>
                        <th>Deadline</th>
                        <th>Officer answerable</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($breaches as $app)
                        @php
                            $sla = $app->status === \App\Services\WorkflowService::PENDING_ADMIN_APPROVAL
                                ? $app->adminApprovalSla()
                                : $app->assessmentSla();
                            $officer = $app->status === \App\Services\WorkflowService::PENDING_ADMIN_APPROVAL
                                ? $app->administrator?->name
                                : $app->districtOfficer?->name;
                        @endphp
                        <tr>
                            <td class="nowrap">
                                <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                            </td>
                            <td>{{ $app->applicant?->full_name }}</td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                            <td class="nowrap">
                                <span class="badge badge-danger">{{ $sla['label'] }}</span>
                            </td>
                            <td class="{{ $officer ? '' : 'row-muted' }}">{{ $officer ?? 'Not assigned' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-head">
            <h3>Recent activity</h3>
            <div class="card-actions">
                <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">View all</a>
                @can('do', 'applications.create')
                    <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                        @include('partials.icon', ['name' => 'plus']) New application
                    </a>
                @endcan
            </div>
        </div>

        @if ($recent->isEmpty())
            <div class="empty">
                @include('partials.icon', ['name' => 'empty'])
                <p class="mb-0">No applications yet.</p>
                @if (auth()->user()->hasPermission('applications.create'))
                    <p class="mt-1">
                        <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                            Create the first application
                        </a>
                    </p>
                @endif
            </div>
        @else
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Application</th>
                        <th>Applicant</th>
                        <th>Property</th>
                        <th>District</th>
                        <th>Status</th>
                        <th class="num">Arrears balance</th>
                        <th>Updated</th>
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
                            <td>{{ $app->district?->name }}</td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
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
            <div class="card-head"><h3>Caseload by stage</h3></div>
            <div class="card-body">
                <div class="inline-list">
                    @foreach ($byStatus as $status => $count)
                        <span class="badge badge-{{ \App\Services\WorkflowService::TONES[$status] ?? 'neutral' }}">
                            {{ $labels[$status] ?? $status }}
                            <strong>{{ $count }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

@endsection
