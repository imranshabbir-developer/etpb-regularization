@extends('layouts.app')

@section('title', __('Objections'))
@section('heading', __('Objections awaiting decision'))

@section('content')

<div class="page-head">
    <h1>{{ __('Objections') }}</h1>
    <p class="lede">
        {{ __('Rent cannot be fixed while an objection is undecided.') }}
        <span class="clause">{{ __('Clause 10(i)(d)') }}</span>
    </p>
</div>

<div class="tiles">
    <div class="tile {{ ($stats['undecided'] ?? 0) > 0 ? 'is-warn' : '' }}">
        <div class="tile-label">{{ __('Undecided') }}</div>
        <div class="tile-value">{{ number_format($stats['undecided'] ?? $objections->total()) }}</div>
        <div class="tile-sub">{{ __('Holding rent fixation') }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Filed') }}</div>
        <div class="tile-value">{{ number_format($stats['filed'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('Awaiting listing') }}</div>
    </div>
    <div class="tile">
        <div class="tile-label">{{ __('Under hearing') }}</div>
        <div class="tile-value">{{ number_format($stats['hearing'] ?? 0) }}</div>
        <div class="tile-sub">{{ __('On the hearing diary') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>{{ __(':count undecided', ['count' => number_format($objections->total())]) }}</h3></div>

    @if ($objections->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'check'])
            <p class="mb-0">{{ __('No objection is outstanding.') }}</p>
        </div>
    @else
        <div class="table-wrap" style="border:0;border-radius:0">
            <table class="data">
                <thead>
                <tr><th>{{ __('Objection') }}</th><th>{{ __('Application') }}</th><th>{{ __('Objector') }}</th><th>{{ __('Filed') }}</th><th>{{ __('In time') }}</th><th>{{ __('Plea') }}</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($objections as $o)
                    <tr>
                        <td class="nowrap" style="font-size:.8rem">{{ $o->objection_no }}</td>
                        <td class="nowrap">
                            <a href="{{ route('applications.show', $o->application_id) }}">{{ $o->application_no }}</a>
                            <div class="faint" style="font-size:.75rem">{{ __($o->district ?? '') }}</div>
                        </td>
                        <td>{{ $o->objector_name }}</td>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($o->filed_on)->translatedFormat('d-m-Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $o->is_within_time ? 'good' : 'danger' }}">
                                {{ $o->is_within_time ? __('Yes') : __('Out of time') }}
                            </span>
                        </td>
                        <td style="max-width:320px;font-size:.82rem">
                            {{ \Illuminate\Support\Str::limit($o->plea, 160) }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('due-process.index', $o->application_id) }}"
                               class="btn btn-outline btn-sm">{{ __('Decide') }}</a>
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
