<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') — {{ __('Regularization of Possession') }} | {{ __('ETPB') }}</title>
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
    @vite(['resources/css/app.css'])
</head>
<body>
@include('partials.lang-switch', ['class' => 'is-auth'])
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

        <div class="auth-card login-wow-card">
            <p class="faint mb-1" style="font-size:.85rem">{{ __('Error') }} @yield('code')</p>
            <h1>@yield('title')</h1>
            <p class="lede">@yield('message')</p>
            <a class="btn btn-primary w-full justify-center" href="{{ url('/') }}">{{ __('Return to the portal') }}</a>
        </div>
    </div>
</div>
</body>
</html>
