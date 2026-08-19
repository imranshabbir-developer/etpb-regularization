@extends('layouts.app')

@section('title', 'Litigation')
@section('heading', 'Litigation register')

@section('content')

<div class="page-head">
    <h1>Litigation</h1>
    <p class="lede">
        A pending case or a subsisting restraining order parks the application until it is
        disposed of.
    </p>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ number_format($litigations->total()) }} cases</h3>
        <div class="card-actions">
            <a href="{{ route('reports.registers', 'litigation') }}" class="btn btn-outline btn-sm">
                Sub judice register
            </a>
        </div>
    </div>

    @if ($litigations->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'gavel'])
            <p class="mb-0">No case on record.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>Case</th><th>Court</th><th>Application</th><th>Status</th><th>Next hearing</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($litigations as $l)
                    <tr>
                        <td class="nowrap">
                            <strong>{{ $l->case_no }}</strong>
                            <div class="faint" style="font-size:.75rem">
                                {{ ucwords(strtolower(str_replace('_', ' ', $l->case_type))) }}
                            </div>
                        </td>
                        <td>{{ $l->court_name }}</td>
                        <td class="nowrap">
                            @if ($l->application_id)
                                <a href="{{ route('applications.show', $l->application_id) }}">{{ $l->application_no }}</a>
                                <div class="faint" style="font-size:.75rem">{{ $l->applicant }}</div>
                            @else
                                <span class="faint">not linked</span>
                            @endif
                        </td>
                        <td>
                            @if ($l->is_pending)<span class="badge badge-warn">Pending</span>@endif
                            @if ($l->has_restraining_order)<span class="badge badge-danger">Stay</span>@endif
                            @if ($l->is_direction_case)<span class="badge badge-info">Direction</span>@endif
                            @if (! $l->is_pending && ! $l->has_restraining_order)
                                <span class="badge badge-good">{{ ucfirst(strtolower($l->outcome)) }}</span>
                            @endif
                        </td>
                        <td class="nowrap">
                            @if ($l->next_hearing_date)
                                @php $d = \Illuminate\Support\Carbon::parse($l->next_hearing_date); @endphp
                                <span class="badge badge-{{ $d->isPast() ? 'danger' : 'neutral' }}">
                                    {{ $d->format('d-m-Y') }}
                                </span>
                            @else
                                <span class="faint">not set</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($l->application_id)
                                <a href="{{ route('occupancy.index', $l->application_id) }}"
                                   class="btn btn-outline btn-sm">Manage</a>
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
