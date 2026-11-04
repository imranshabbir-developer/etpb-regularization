@extends('layouts.app')

@section('title', __('Audit log'))
@section('heading', __('Audit log'))

@section('content')

<div class="page-head">
    <h1>{{ __('Audit log') }}</h1>
    <p class="lede">
        {{ __('Append-only. Nothing in the application writes to this screen and nothing deletes from it — that is the point of having one.') }}
    </p>
</div>

<div class="card">
    <div class="card-head"><h3>{{ __(':count entries', ['count' => number_format($logs->total())]) }}</h3></div>
    <div class="card-body tight">
        <form method="GET" action="{{ route('admin.audit') }}" class="flex flex-wrap items-end gap-3">
            <div class="field mb-0 min-w-[160px]">
                <label for="event">{{ __('Event') }}</label>
                <select id="event" name="event" class="select">
                    <option value="">{{ __('All events') }}</option>
                    @foreach ($events as $e)
                        <option value="{{ $e }}" @selected(($filters['event'] ?? '') === $e)>{{ __(ucfirst($e ?? '')) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field mb-0 min-w-[180px]">
                <label for="user">{{ __('User') }}</label>
                <select id="user" name="user" class="select">
                    <option value="">{{ __('Everyone') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((int) ($filters['user'] ?? 0) === $u->id)>{{ __($u->name) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">{{ __('Apply') }}</button>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.audit') }}">{{ __('Reset') }}</a>
        </form>
    </div>

    @if ($logs->isEmpty())
        <div class="empty">
            @include('partials.icon', ['name' => 'shield'])
            <p class="mb-0">{{ __('No entries match these filters.') }}</p>
        </div>
    @else
        <div class="table-wrap border-0 rounded-none">
            <table class="data">
                <thead>
                <tr><th>{{ __('When') }}</th><th>{{ __('Who') }}</th><th>{{ __('Event') }}</th><th>{{ __('Record') }}</th><th>{{ __('Detail') }}</th><th>IP</th></tr>
                </thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($log->created_at)->translatedFormat('d-m-Y H:i') }}</td>
                        <td>
                            {{ $log->user_name ?? '—' }}
                            @if ($log->user_role)
                                <div class="faint text-[.75rem]">
                                    {{ __(ucwords(strtolower(str_replace('_', ' ', $log->user_role)))) }}
                                </div>
                            @endif
                        </td>
                        <td><span class="badge badge-neutral">{{ __(ucfirst($log->event ?? '')) }}</span></td>
                        <td class="text-[.8rem]">
                            {{ $log->table_name ?? class_basename($log->auditable_type ?? '') }}
                            @if ($log->auditable_id) #{{ $log->auditable_id }} @endif
                        </td>
                        <td class="text-[.82rem]">{{ $log->description }}</td>
                        <td class="nowrap faint text-[.78rem]">{{ $log->ip_address }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-foot">{{ $logs->links() }}</div>
        @endif
    @endif
</div>

<div class="card">
    <div class="card-head"><h3>{{ __('Recent sign-in attempts') }}</h3></div>
    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>{{ __('When') }}</th><th>{{ __('Identifier') }}</th><th>{{ __('Result') }}</th><th>{{ __('Reason') }}</th><th>IP</th></tr>
            </thead>
            <tbody>
            @forelse ($logins as $a)
                <tr>
                    <td class="nowrap">{{ \Illuminate\Support\Carbon::parse($a->attempted_at)->translatedFormat('d-m-Y H:i') }}</td>
                    <td class="text-[.82rem]">{{ $a->identifier }}</td>
                    <td>
                        <span class="badge badge-{{ $a->successful ? 'good' : 'danger' }}">
                            {{ $a->successful ? __('Success') : __('Failed') }}
                        </span>
                    </td>
                    <td class="faint text-[.8rem]">{{ $a->failure_reason ? __($a->failure_reason) : '—' }}</td>
                    <td class="nowrap faint text-[.78rem]">{{ $a->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="row-muted">{{ __('No sign-in attempts recorded.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
