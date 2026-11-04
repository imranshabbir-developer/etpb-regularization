@extends('layouts.app')

@section('title', __('Change password'))
@section('heading', __('Change password'))

@section('content')

    <div class="container-narrow">
        <div class="page-head">
            <h1>{{ __('Change your password') }}</h1>
            <p class="lede">
                @if (auth()->user()->force_password_change)
                    {{ __('Your account still uses the password issued at commissioning. Choose a new one before continuing.') }}
                @else
                    {{ __('Choose a new password for your account.') }}
                @endif
            </p>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="field">
                        <label for="current_password">{{ __('Current password') }} <span class="req">*</span></label>
                        <input type="password" id="current_password" name="current_password"
                               class="input @error('current_password') is-invalid @enderror"
                               required autocomplete="current-password" autofocus>
                        @error('current_password')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="password">{{ __('New password') }} <span class="req">*</span></label>
                        <input type="password" id="password" name="password"
                               class="input @error('password') is-invalid @enderror"
                               required autocomplete="new-password">
                        <p class="hint">
                            {{ __('At least 12 characters, with upper and lower case letters, a number and a symbol. Passwords found in known breach lists are rejected.') }}
                        </p>
                        @error('password')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">{{ __('Confirm new password') }} <span class="req">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="input" required autocomplete="new-password">
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary">{{ __('Change password') }}</button>
                        @unless (auth()->user()->force_password_change)
                            <a href="{{ route('dashboard') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                        @endunless
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
