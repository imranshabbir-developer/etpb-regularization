@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')

@php $first = explode(' ', trim(auth()->user()->name))[0]; @endphp

<div class="page-head">
    <h1>{{ now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening') }},
        {{ $first }}</h1>
    <p class="lede">
        {{ auth()->user()->primaryRole()?->name }}
        @if (auth()->user()->district) &middot; {{ auth()->user()->district->name }} @endif
        &middot; what is on your desk today
    </p>
</div>

{{-- ---------- Work, not statistics: each tile is a link to the work ---------- --}}
@if (! empty($work))
    <div class="tiles">
        @foreach ($work as $tile)
            <a href="{{ $tile['route'] }}" class="tile {{ $tile['tone'] }} no-underline hover:no-underline
                      hover:shadow-md transition block">
                <div class="tile-label">{{ $tile['label'] }}</div>
                <div class="tile-value">{{ is_numeric($tile['value']) ? number_format($tile['value']) : $tile['value'] }}</div>
                <div class="tile-sub">{{ $tile['sub'] }}</div>
            </a>
        @endforeach
    </div>
@endif

<div class="grid-main items-start gap-[1.15rem]">
    <div>
        <div class="card">
            <div class="card-head">
                <h3>Recently touched</h3>
                <div class="card-actions">
                    <a href="{{ route('applications.index') }}" class="btn btn-outline btn-sm">All applications</a>
                </div>
            </div>

            @if ($recent->isEmpty())
                <div class="empty">
                    @include('partials.icon', ['name' => 'empty'])
                    <p class="mb-0">Nothing yet.</p>
                </div>
            @else
                <div class="table-wrap border-0 rounded-none">
                    <table class="data">
                        <thead>
                        <tr><th>Application</th><th>Applicant</th><th>Payment</th><th>Stage</th><th>Updated</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($recent as $app)
                            <tr>
                                <td class="nowrap">
                                    <a href="{{ route('applications.show', $app) }}">{{ $app->application_no }}</a>
                                </td>
                                <td>{{ $app->applicant?->full_name }}</td>
                                <td>
                                    <span class="badge badge-{{ $app->payment_status === 'PAID' ? 'good' : 'warn' }}">
                                        {{ $app->payment_status }}
                                    </span>
                                </td>
                                <td><span class="badge badge-{{ $app->statusTone() }}">{{ $app->statusLabel() }}</span></td>
                                <td class="nowrap faint" title="{{ $app->updated_at?->format('d-m-Y H:i') }}">{{ $app->updated_at?->format('d M') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-head"><h3>Caseload</h3></div>
            <div class="card-body">
                @if ($byStatus->isEmpty())
                    <p class="muted mb-0">No applications yet.</p>
                @else
                    <div class="inline-list">
                        @foreach ($byStatus as $status => $count)
                            <span class="badge badge-{{ \App\Services\WorkflowService::TONES[$status] ?? 'neutral' }}">
                                {{ $labels[$status] ?? $status }} <strong>{{ $count }}</strong>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>The two clocks</h3></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Assessment</dt>
                    <dd>60 days from the first notice <span class="clause">Clause 10(i)(e)</span></dd>
                    <dt>Approval</dt>
                    <dd>One month, with reasons <span class="clause">Clause 3(ii)(d)</span></dd>
                    <dt>Objections</dt>
                    <dd>15 days from service <span class="clause">Clause 10(i)(c)</span></dd>
                </dl>
                <p class="hint mb-0">
                    An application whose deposit is still <strong>PENDING</strong> cannot be
                    processed at all &mdash; the clocks do not start until it is paid.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
