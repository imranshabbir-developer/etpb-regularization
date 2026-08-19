@extends('layouts.app')

@section('title', 'Applications')
@section('heading', 'Applications')

@section('content')

    <div class="page-head">
        <h1>Applications</h1>
        <p class="lede">Regularization of possession — Clause 3(ii), Scheme 1977.</p>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Filter</h3>
            <div class="card-actions">
                @can('do', 'applications.create')
                    <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm">
                        @include('partials.icon', ['name' => 'plus']) New application
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body tight">
            <form method="GET" action="{{ route('applications.index') }}">
                <div class="grid-4">
                    <div class="field">
                        <label for="q">Search</label>
                        <input type="search" id="q" name="q" class="input"
                               value="{{ $filters['q'] ?? '' }}"
                               placeholder="Application no., applicant, CNIC, property no.">
                    </div>
                    <div class="field">
                        <label for="status">Stage</label>
                        <select id="status" name="status" class="select">
                            <option value="">All stages</option>
                            @foreach ($statuses as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="district">District</label>
                        <select id="district" name="district" class="select">
                            <option value="">All districts</option>
                            @foreach ($districts as $d)
                                <option value="{{ $d->id }}" @selected((int) ($filters['district'] ?? 0) === $d->id)>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="display:flex;align-items:flex-end;gap:.5rem">
                        <button class="btn btn-primary" type="submit">Apply</button>
                        <a class="btn btn-ghost" href="{{ route('applications.index') }}">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>{{ number_format($applications->total()) }} application(s)</h3>
        </div>

        @if ($applications->isEmpty())
            <div class="empty">
                @include('partials.icon', ['name' => 'empty'])
                <p class="mb-0">No applications match these filters.</p>
            </div>
        @else
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Application no.</th>
                        <th>Applicant</th>
                        <th>CNIC</th>
                        <th>Property</th>
                        <th>District</th>
                        <th>Payment</th>
                        <th>Stage</th>
                        <th class="num">Assessed rent</th>
                        <th class="num">Arrears balance</th>
                        <th>Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($applications as $app)
                        <tr>
                            <td class="nowrap">
                                <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                                @if ($app->is_sub_judice)
                                    <span class="badge badge-warn" title="Pending before a court or stayed">Sub judice</span>
                                @endif
                            </td>
                            <td>
                                {{ $app->applicant?->full_name }}
                                <div class="faint" style="font-size:.78rem">
                                    {{ $app->applicant?->parentage_type === 'HUSBAND' ? 'w/o' : 's/o' }}
                                    {{ $app->applicant?->parentage_name }}
                                </div>
                            </td>
                            <td class="nowrap num">{{ $app->applicant?->maskedCnic() }}</td>
                            <td class="nowrap">
                                {{ $app->property?->property_no }}@if ($app->property?->sub_unit_no)/{{ $app->property->sub_unit_no }}@endif
                            </td>
                            <td>{{ $app->district?->name }}</td>
                            <td>
                                <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                                    {{ $app->payment_status }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
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
