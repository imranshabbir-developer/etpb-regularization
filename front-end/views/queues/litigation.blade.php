@extends('layouts.app')

@section('title', __('Litigation'))
@section('heading', __('Litigation register'))

@section('content')

<div class="page-head">
    <h1>{{ __('Litigation') }}</h1>
    <p class="lede">
        {{ __('A pending case or a subsisting restraining order parks the application until it is disposed of.') }}
    </p>
</div>

<div class="tiles">
    <div class="tile">
        <div class="tile-label">{{ __('On record') }}</div>
        <div class="tile-value">{{ number_format($stats['total'] ?? $litigations->total()) }}</div>
        <div class="tile-sub">{{ __('All litigation entries in scope') }}</div>
    </div>
    <div class="tile {{ ($stats['pending'] ?? 0) > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Pending / sub judice') }}</div>
        <div class="tile-value">{{ number_format($stats['pending'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Applications parked until disposed') }}</div>
    </div>
    <div class="tile {{ ($stats['restraining'] ?? 0) > 0 ? 'is-danger' : '' }}">
        <div class="tile-label">{{ __('Restraining orders') }}</div>
        <div class="tile-value">{{ number_format($stats['restraining'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Active stays on the file') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ __(':count cases', ['count' => number_format($litigations->total())]) }}</h3>
        <div class="card-actions">
            <a href="{{ route('reports.registers', 'litigation') }}" class="btn btn-outline btn-sm">
                {{ __('Sub judice register') }}
            </a>
        </div>
    </div>

    @if ($litigations->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'gavel'])
            <p class="mb-0">{{ __('No case on record.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>{{ __('Case') }}</th><th>{{ __('Court') }}</th><th>{{ __('Application') }}</th><th>{{ __('Status') }}</th><th>{{ __('Next hearing') }}</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($litigations as $l)
                    <tr>
                        <td class="nowrap">
                            <strong>{{ $l->case_no }}</strong>
                            <div class="faint" style="font-size:.75rem">
                                {{ __(ucwords(strtolower(str_replace('_', ' ', $l->case_type)))) }}
                            </div>
                        </td>
                        <td>{{ $l->court_name }}</td>
                        <td class="nowrap">
                            @if ($l->application_id)
                                <a href="{{ route('applications.show', $l->application_id) }}">{{ $l->application_no }}</a>
                                <div class="faint" style="font-size:.75rem">{{ $l->applicant }}</div>
                            @else
                                <span class="faint">{{ __('not linked') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($l->is_pending)<span class="badge badge-warn">{{ __('Pending') }}</span>@endif
                            @if ($l->has_restraining_order)<span class="badge badge-danger">{{ __('Stay') }}</span>@endif
                            @if ($l->is_direction_case)<span class="badge badge-info">{{ __('Direction') }}</span>@endif
                            @if (! $l->is_pending && ! $l->has_restraining_order)
                                <span class="badge badge-good">{{ __(ucfirst(strtolower($l->outcome))) }}</span>
                            @endif
                        </td>
                        <td class="nowrap">
                            @if ($l->next_hearing_date)
                                @php $d = \Illuminate\Support\Carbon::parse($l->next_hearing_date); @endphp
                                <span class="badge badge-{{ $d->isPast() ? 'danger' : 'neutral' }}">
                                    {{ $d->translatedFormat('d-m-Y') }}
                                </span>
                            @else
                                <span class="faint">{{ __('not set') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($l->application_id)
                                <a href="{{ route('occupancy.index', $l->application_id) }}"
                                   class="btn btn-outline btn-sm">{{ __('Manage') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($litigations->hasPages())
            <div class="card-foot">{{ $litigations->links() }}</div>
        @endif
    @endif
</div>

@endsection
