@extends('layouts.app')

@section('title', __('Users and roles'))
@section('heading', __('Users and roles'))

@section('content')

<div class="page-head">
    <h1>{{ __('Users and roles') }}</h1>
    <p class="lede">
        {{ __('Officer accounts. Members of the public register themselves and are given the applicant role automatically.') }}
    </p>
</div>

<div class="card">
    <div class="card-head"><h3>{{ __('Add an officer') }}</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
            @csrf
            <div class="grid-3">
                <div class="field">
                    <label for="name">{{ __('Name') }} <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="input" required maxlength="150">
                </div>
                <div class="field">
                    <label for="email">{{ __('Official email') }} <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input" required maxlength="191">
                </div>
                <div class="field">
                    <label for="cnic">{{ __('CNIC') }}</label>
                    <input type="text" id="cnic" name="cnic" class="input"
                           inputmode="numeric" pattern="[0-9]{13}" maxlength="13">
                </div>
                <div class="field">
                    <label for="role_id">{{ __('Role') }} <span class="req">*</span></label>
                    <select id="role_id" name="role_id" class="select" required>
                        @foreach ($roles as $r)
                            @continue($r->code === 'APPLICANT')
                            <option value="{{ $r->id }}">{{ __($r->name ?? '') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="designation">{{ __('Designation') }}</label>
                    <input type="text" id="designation" name="designation" class="input" maxlength="120">
                </div>
                <div class="field">
                    <label for="contact">{{ __('Contact') }}</label>
                    <input type="text" id="contact" name="contact" class="input" maxlength="20">
                </div>
                <div class="field">
                    <label for="district_id">{{ __('District') }}</label>
                    <select id="district_id" name="district_id" class="select">
                        <option value="">{{ __('Not district-bound') }}</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}">{{ __($d->name ?? '') }}</option>
                        @endforeach
                    </select>
                    <p class="hint">{{ __('A District Officer only sees their own district.') }}</p>
                </div>
                <div class="field">
                    <label for="office_id">{{ __('Office') }}</label>
                    <select id="office_id" name="office_id" class="select">
                        <option value="">{{ __('Not set') }}</option>
                        @foreach ($offices as $o)
                            <option value="{{ $o->id }}">{{ __($o->name ?? '') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">
                @include('partials.icon', ['name' => 'plus']) {{ __('Create account') }}
            </button>
            <p class="hint mt-1 mb-0">
                {{ __('A one-time password is generated and shown once. The officer must change it at first sign-in.') }}
            </p>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>{{ __(':count accounts', ['count' => number_format($users->total())]) }}</h3>
    </div>
    <div class="card-body tight">
        <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-end gap-3">
            <div class="field mb-0 min-w-[200px]">
                <label for="q">{{ __('Search') }}</label>
                <input type="search" id="q" name="q" class="input" value="{{ $filters['q'] ?? '' }}"
                       placeholder="{{ __('Name, email or CNIC') }}">
            </div>
            <div class="field mb-0 min-w-[180px]">
                <label for="role">{{ __('Role') }}</label>
                <select id="role" name="role" class="select">
                    <option value="">{{ __('All roles') }}</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->code }}" @selected(($filters['role'] ?? '') === $r->code)>{{ __($r->name ?? '') }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">{{ __('Apply') }}</button>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.users') }}">{{ __('Reset') }}</a>
        </form>
    </div>

    <div class="table-wrap border-0 rounded-none">
        <table class="data">
            <thead>
            <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th><th>{{ __('District') }}</th><th>{{ __('Status') }}</th><th>{{ __('Last signed in') }}</th><th></th></tr>
            </thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>
                        {{ __($u->name) }}
                        @if ($u->designation)
                            <div class="faint text-[.78rem]">{{ __($u->designation) }}</div>
                        @endif
                    </td>
                    <td class="text-[.82rem]">{{ $u->email }}</td>
                    <td>
                        @foreach ($u->roles as $r)
                            <span class="badge badge-neutral">{{ __($r->name ?? '') }}</span>
                        @endforeach
                    </td>
                    <td>{{ $u->district?->name ? __($u->district->name) : '—' }}</td>
                    <td>
                        @php $tone = match ($u->status) {
                            'ACTIVE' => 'good', 'SUSPENDED', 'LOCKED' => 'danger', default => 'neutral',
                        }; @endphp
                        <span class="badge badge-{{ $tone }}">{{ __(ucfirst(strtolower($u->status ?? ''))) }}</span>
                        @if ($u->force_password_change)
                            <div><span class="badge badge-warn">{{ __('Must change password') }}</span></div>
                        @endif
                        @if ($u->isLocked())
                            <div><span class="badge badge-danger">{{ __('Locked') }}</span></div>
                        @endif
                    </td>
                    <td class="nowrap faint text-[.82rem]">
                        {{ $u->last_login_at?->diffForHumans() ?? __('never') }}
                    </td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.users.update', $u) }}"
                              class="flex gap-1 items-center justify-end">
                            @csrf
                            <select name="action" class="select" style="font-size:.78rem;padding:.2rem .4rem;width:auto">
                                <option value="RESET_PASSWORD">{{ __('Reset password') }}</option>
                                @if ($u->status === 'ACTIVE')
                                    <option value="SUSPEND">{{ __('Suspend') }}</option>
                                @else
                                    <option value="ACTIVATE">{{ __('Activate') }}</option>
                                @endif
                                <option value="UNLOCK">{{ __('Unlock') }}</option>
                            </select>
                            <button class="btn btn-outline btn-sm" type="submit">{{ __('Go') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="card-foot">{{ $users->links() }}</div>
    @endif
</div>

@endsection
