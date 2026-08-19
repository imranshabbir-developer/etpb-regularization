@extends('layouts.app')

@section('title', 'Objections')
@section('heading', 'Objections awaiting decision')

@section('content')

<div class="page-head">
    <h1>Objections</h1>
    <p class="lede">
        Rent cannot be fixed while an objection is undecided.
        <span class="clause">Clause 10(i)(d)</span>
    </p>
</div>

<div class="card">
    <div class="card-head"><h3>{{ number_format($objections->total()) }} undecided</h3></div>

    @if ($objections->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">No objection is outstanding.</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>Objection</th><th>Application</th><th>Objector</th><th>Filed</th><th>In time</th><th>Plea</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($objections as $o)
                    <tr>
                        <td class="nowrap" style="font-size:.8rem">{{ $o->objection_no }}</td>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $o->application_id) }}">{{ $o->application_no }}</a>
                            <div class="faint" style="font-size:.75rem">{{ $o->district }}</div>
                        </td>
                        <td>{{ $o->objector_name }}</td>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($o->filed_on)->format('d-m-Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $o->is_within_time ? 'good' : 'danger' }}">
                                {{ $o->is_within_time ? 'Yes' : 'Out of time' }}
                            </span>
                        </td>
                        <td style="max-width:320px;font-size:.82rem">
                            {{ \Illuminate\Support\Str::limit($o->plea, 160) }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('due-process.index', $o->application_id) }}"
                               class="btn btn-outline btn-sm">Decide</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($objections->hasPages())
            <div class="card-foot">{{ $objections->links() }}</div>
        @endif
    @endif
</div>

@endsection
