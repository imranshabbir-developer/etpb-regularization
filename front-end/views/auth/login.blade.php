<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('Sign in') }} — {{ __('Regularization of Possession') }} | {{ __('ETPB') }}</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
    <script>
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('etpb-theme'); } catch (e) { /* ignore */ }
            var theme = (stored === 'light' || stored === 'dark')
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    @include('partials.i18n-script')
    @vite(['resources/css/app.css'])
</head>
<body>
@include('partials.lang-switch', ['class' => 'is-auth'])
@php
    $demoPassword = 'Etpb@2026#Change';
    $roleRail = [
        ['label' => __('Admin'),     'email' => 'admin@etpb.gov.pk',       'short' => 'SA'],
        ['label' => __('Chairman'),  'email' => 'chairman@etpb.gov.pk',    'short' => 'CH'],
        ['label' => __('Secretary'), 'email' => 'secretary@etpb.gov.pk',   'short' => 'SE'],
        ['label' => __('Admin LHR'), 'email' => 'admin.lhr@etpb.gov.pk',   'short' => 'AD'],
        ['label' => __('DO / DA'),   'email' => 'do.lhr@etpb.gov.pk',      'short' => 'DO'],
        ['label' => __('Dealing'),   'email' => 'da.lhr@etpb.gov.pk',      'short' => 'DA'],
        ['label' => __('Accounts'),  'email' => 'accounts.lhr@etpb.gov.pk','short' => 'AC'],
        ['label' => __('Legal'),     'email' => 'legal.lhr@etpb.gov.pk',   'short' => 'LG'],
        ['label' => __('Auditor'),   'email' => 'audit@etpb.gov.pk',       'short' => 'AU'],
    ];
@endphp

<div class="login-wow">
    <div class="login-wow-orb login-wow-orb-a" aria-hidden="true"></div>
    <div class="login-wow-orb login-wow-orb-b" aria-hidden="true"></div>
    <div class="login-wow-grid" aria-hidden="true"></div>

    <div class="login-wow-center">
        <div class="login-wow-brand">
            <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
            <div>
                <strong>{{ __('Evacuee Trust Property Board') }}</strong>
                <span>{{ __('Regularization of Possession') }}</span>
            </div>
        </div>

        <div class="auth-card auth-card-login login-wow-card">
            <h1>{{ __('Sign in') }}</h1>
            <p class="lede">{{ __('Use the credentials issued by your district office.') }}</p>

            @if (session('status'))
                <div class="alert alert-good" role="status">
                    @include('partials.icon', ['name' => 'check'])
                    <div><p class="mb-0">{{ session('status') }}</p></div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @include('partials.icon', ['name' => 'alert'])
                    <div>
                        @foreach ($errors->all() as $message)
                            <p class="mb-0">{{ $message }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" id="loginForm" novalidate>
                @csrf

                <div class="field">
                    <label for="email">{{ __('Official email') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autocomplete="username" autofocus
                           placeholder="name@etpb.gov.pk">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">{{ __('Password') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password"
                           class="input @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field" style="display:flex;align-items:center;gap:.45rem">
                    <input type="checkbox" id="remember" name="remember" value="1" style="margin:0">
                    <label for="remember" style="margin:0;font-weight:500">{{ __('Keep me signed in on this device') }}</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                    @include('partials.icon', ['name' => 'lock']) {{ __('Sign in') }}
                </button>
            </form>

            <hr class="divider">

            <p style="font-size:.88rem;margin:0 0 .75rem">
                {{ __('Applying for the first time?') }}
                <a href="{{ route('register') }}"><strong>{{ __('Create an applicant account') }}</strong></a>
            </p>

            <p class="faint" style="font-size:.8rem;margin:0">
                {{ __('Access is logged. Sign-in attempts and record views are audited.') }}
            </p>
        </div>
    </div>

    <aside class="login-role-rail" aria-label="{{ __('Demo role autofill') }}">
        <p class="login-role-rail-title">{{ __('Roles') }}</p>
        @foreach ($roleRail as $role)
            <button type="button"
                    class="login-role-chip"
                    title="{{ $role['label'] }} — {{ __('click to fill email & password') }}"
                    data-email="{{ $role['email'] }}"
                    data-password="{{ $demoPassword }}">
                <span class="login-role-short">{{ $role['short'] }}</span>
                <span class="login-role-label">{{ $role['label'] }}</span>
            </button>
        @endforeach
        <p class="login-role-rail-hint">{{ __('Click a role to autofill. Then press Sign in.') }}</p>
    </aside>
</div>

<script>
(function () {
    var email = document.getElementById('email');
    var password = document.getElementById('password');
    document.querySelectorAll('.login-role-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            email.value = btn.getAttribute('data-email') || '';
            password.value = btn.getAttribute('data-password') || '';
            email.focus();
            document.querySelectorAll('.login-role-chip').forEach(function (b) {
                b.classList.remove('is-active');
            });
            btn.classList.add('is-active');
        });
    });
})();
</script>

@include('partials.promo-modal')
</body>
</html>
