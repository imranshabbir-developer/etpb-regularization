@extends('layouts.app')

@section('title', 'Assessment queue')
@section('heading', 'Rent assessment queue')

@section('content')

<div class="page-head">
    <h1>Assessment</h1>
    <p class="lede">
        Ordered by how close the sixty-day limit is. <span class="clause">Clause 10(i)(e)</span>
    </p>
</div>

<div class="card">
    <div class="card-head"><h3>{{ number_format($applications->total()) }} in assessment</h3></div>

    @if ($applications->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'scale'])
            <p class="mb-0">Nothing in assessment.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>Application</th><th>Applicant</th><th>District</th><th>Stage</th><th>Assessment due</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($applications as $app)
                    @php $sla = $app->assessmentSla(); @endphp
                    <tr>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                        </td>
                        <td>{{ $app->applicant?->full_name }}</td>
                        <td>{{ $app->district?->name }}</td>
                        <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                        <td class="nowrap">
                            @if ($sla['applies'])
                                <span class="badge badge-{{ $sla['tone'] }}">{{ $sla['label'] }}</span>
                            @else
                                <span class="faint">not started</span>
                            @endif
                        </td>
                        <td class="text-end nowrap">
                            <a href="{{ route('assessment.show', $app) }}" class="btn btn-outline btn-sm">Assess</a>
                            <a href="{{ route('due-process.index', $app) }}" class="btn btn-ghost btn-sm">Notices</a>
                        </td>
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
