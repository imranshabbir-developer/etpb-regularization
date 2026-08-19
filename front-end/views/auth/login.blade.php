<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — Regularization of Possession | ETPB</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="auth-page auth-page-login">

    <aside class="auth-aside">
        <div class="auth-aside-brand">
            <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
            <span class="brand-text">
                <strong>Evacuee Trust Property Board</strong>
                <span>Government of Pakistan</span>
            </span>
        </div>

        <div class="auth-aside-hero">
            <h2 class="auth-hero-title">Regularization of Possession</h2>
        </div>

        <div class="auth-aside-footer">
            <p>
                Applications by existing occupants of urban evacuee trust properties to be
                treated as tenants under Clause 3(ii) of the Scheme for the Management and
                Disposal of Urban Evacuee Trust Properties, 1977.
            </p>
            <div class="auth-legal">A secure digital portal for regularization workflow.</div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card auth-card-login">
            <h1>Sign in</h1>
            <p class="lede">Use the official credentials issued by your district office.</p>

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

            <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                @csrf

                <div class="field">
                    <label for="email">Official email <span class="req" aria-hidden="true">*</span></label>
                    <input type="email" id="email" name="email" class="input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autocomplete="username" autofocus
                           placeholder="name@etpb.gov.pk">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">Password <span class="req" aria-hidden="true">*</span></label>
                    <input type="password" id="password" name="password"
                           class="input @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field" style="display:flex;align-items:center;gap:.45rem">
                    <input type="checkbox" id="remember" name="remember" value="1" style="margin:0">
                    <label for="remember" style="margin:0;font-weight:500">Keep me signed in on this device</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                    @include('partials.icon', ['name' => 'lock']) Sign in
                </button>
            </form>

            <hr class="divider">

            <p style="font-size:.88rem;margin:0 0 .75rem">
                Applying for the first time?
                <a href="{{ route('register') }}"><strong>Create an applicant account</strong></a>
            </p>

            <p class="faint" style="font-size:.8rem;margin:0">
                Access is logged. Sign-in attempts, record views and downloads are recorded
                against your account in the audit trail.
            </p>
        </div>
    </main>
</div>
</body>
</html>
