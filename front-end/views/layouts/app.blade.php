<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark" id="colorSchemeMeta">
    <title>@yield('title', 'Dashboard') — Regularization of Possession | ETPB</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">

    {{-- Apply saved / system theme before paint to avoid a flash. --}}
    <script>
        (function () {
            var KEY = 'etpb-theme';
            var stored = null;
            try { stored = localStorage.getItem(KEY); } catch (e) { /* ignore */ }
            var theme = (stored === 'light' || stored === 'dark')
                ? stored
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>

<div class="shell">

    {{-- Brand block for portal identity. --}}
    <div class="brand-cell">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
            <span class="brand-text">
                <strong>Regularization of Possession</strong>
                <span>Evacuee Trust Property Board</span>
            </span>
        </a>
    </div>

    <header class="topbar">
        <button type="button" class="btn btn-ghost btn-sm nav-toggle" id="navToggle"
                aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">
            @include('partials.icon', ['name' => 'menu'])
        </button>

        <h2 class="topbar-title">@yield('heading', 'Dashboard')</h2>

        <div class="topbar-spacer"></div>

        <button type="button" class="btn btn-ghost btn-sm theme-toggle" id="themeToggle"
                aria-label="Toggle colour theme" title="Toggle light / dark mode">
            <span class="theme-icon-light" aria-hidden="true">@include('partials.icon', ['name' => 'moon'])</span>
            <span class="theme-icon-dark" aria-hidden="true">@include('partials.icon', ['name' => 'sun'])</span>
            <span class="btn-label theme-label">Theme</span>
        </button>

        @auth
            <div class="topbar-user">
                <span class="badge badge-neutral nowrap">{{ auth()->user()->primaryRole()?->name ?? 'User' }}</span>
                <span class="nowrap muted topbar-name">{{ auth()->user()->name }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit" title="Sign out">
                    @include('partials.icon', ['name' => 'logout'])
                    <span class="btn-label">Sign out</span>
                </button>
            </form>
        @endauth
    </header>

    @include('partials.sidebar')

    <main class="main" id="main-content">
        @foreach ([['status', 'good', 'check'], ['error', 'danger', 'alert'], ['warning', 'warn', 'alert']] as [$key, $tone, $icon])
            @if (session($key))
                <div class="alert alert-{{ $tone }}" role="{{ $key === 'status' ? 'status' : 'alert' }}">
                    @include('partials.icon', ['name' => $icon])
                    <div><p class="mb-0">{{ session($key) }}</p></div>
                </div>
            @endif
        @endforeach

        @if ($errors->any() && ! isset($suppressErrorSummary))
            <div class="alert alert-danger" role="alert">
                @include('partials.icon', ['name' => 'alert'])
                <div>
                    <strong>Please correct the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')

        <footer class="appfoot">
            <span>Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977</span>
            <span class="topbar-spacer"></span>
            <span>Regularization of Possession Portal</span>
        </footer>
    </main>
</div>

<div class="scrim" id="navScrim" hidden></div>

@include('partials.help-widget')

@if (request()->routeIs('dashboard'))
    @include('partials.promo-modal')
@endif

<script>
(function () {
    // ---- theme (light / dark) ---------------------------------------------
    var THEME_KEY = 'etpb-theme';
    var themeBtn = document.getElementById('themeToggle');

    function currentTheme() {
        var t = document.documentElement.getAttribute('data-theme');
        return (t === 'dark' || t === 'light') ? t : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
        try { localStorage.setItem(THEME_KEY, theme); } catch (e) { /* ignore */ }
        if (themeBtn) {
            var next = theme === 'dark' ? 'light' : 'dark';
            themeBtn.setAttribute('aria-label', 'Switch to ' + next + ' mode');
            themeBtn.setAttribute('title', 'Switch to ' + next + ' mode');
        }
        document.dispatchEvent(new CustomEvent('etpb:themechange', { detail: { theme: theme } }));
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
        applyTheme(currentTheme());
    }

    // ---- mobile navigation ------------------------------------------------
    var toggle  = document.getElementById('navToggle');
    var sidebar = document.getElementById('sidebar');
    var scrim   = document.getElementById('navScrim');

    function setNav(open) {
        if (!sidebar || !scrim || !toggle) return;
        sidebar.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
        scrim.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setNav(!sidebar.classList.contains('is-open'));
        });
    }
    if (scrim) scrim.addEventListener('click', function () { setNav(false); });

    // Following a link on a phone should close the drawer behind it.
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.closest('a') && window.matchMedia('(max-width: 860px)').matches) {
                setNav(false);
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setNav(false);
    });

})();
</script>
@stack('scripts')
</body>
</html>
