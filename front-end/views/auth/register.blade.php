<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('Register') }} — {{ __('Regularization of Possession') }} | {{ __('ETPB') }}</title>
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
            <h1>{{ __('Create account') }}</h1>
            <p class="lede">{{ __('For applicants. Officers receive credentials from their office.') }}</p>

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

            <form method="POST" action="{{ route('register.store') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="name">{{ __('Full name') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="name" name="name" class="input @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required maxlength="150" autofocus
                           autocomplete="name" placeholder="{{ __('As on CNIC') }}">
                    @error('name')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="cnic">{{ __('CNIC') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                           value="{{ old('cnic') }}" required inputmode="numeric"
                           pattern="[0-9]{13}" maxlength="13" placeholder="{{ __('13 digits, no dashes') }}"
                           autocomplete="off">
                    @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="email">{{ __('Email') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autocomplete="username"
                           placeholder="you@example.com">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="contact">{{ __('Mobile') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="tel" id="contact" name="contact" class="input @error('contact') is-invalid @enderror"
                           value="{{ old('contact') }}" required maxlength="20"
                           placeholder="0300-1234567" autocomplete="tel">
                    @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">{{ __('Password') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password"
                           class="input @error('password') is-invalid @enderror"
                           required autocomplete="new-password" placeholder="{{ __('At least 10 characters') }}">
                    @error('password')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">{{ __('Confirm password') }} <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="input" required autocomplete="new-password">
                </div>

                <div class="field" style="display:flex;align-items:flex-start;gap:.45rem">
                    <input type="checkbox" id="declaration" name="declaration" value="1"
                           style="margin:.25rem 0 0" required>
                    <label for="declaration" style="margin:0;font-weight:500;font-size:.82rem;line-height:1.35">
                        {{ __('I confirm the information I give will be true.') }}
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                    {{ __('Create account') }}
                </button>
            </form>

            <hr class="divider">

            <p style="font-size:.88rem;margin:0">
                {{ __('Already registered?') }}
                <a href="{{ route('login') }}"><strong>{{ __('Sign in') }}</strong></a>
            </p>
        </div>
    </div>
</div>

</body>
</html>
