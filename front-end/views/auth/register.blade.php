<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — Regularization of Possession | ETPB</title>
    <link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="auth-page">

    <aside class="auth-aside pk-stripe">
        <div style="display:flex;align-items:center;gap:.7rem;position:relative;z-index:1">
            <span class="brand-mark">@include('partials.icon', ['name' => 'shield'])</span>
            <span class="brand-text">
                <strong>Evacuee Trust Property Board</strong>
                <span>Government of Pakistan</span>
            </span>
        </div>

        <svg class="auth-crescent" viewBox="0 0 200 200" fill="#fff" aria-hidden="true">
            <path d="M132 100a58 58 0 1 1-38-54.5 46 46 0 1 0 0 109A58 58 0 0 1 132 100z"/>
            <path d="m146 66 6.6 14.6 15.9 1.9-11.8 10.8 3.2 15.7L146 101.4 132.1 109l3.2-15.7-11.8-10.8 15.9-1.9z"/>
        </svg>

        <div class="auth-aside-body">
            <h2>Apply to have your possession regularized</h2>
            <p>
                If you have been in possession of an evacuee trust property since before
                1 January 2010, you may apply to be recorded as its tenant.
            </p>
            <p>
                You will need your CNIC, the particulars of the property, your evidence of
                possession, and a deposit of <strong>Rs. 5,000</strong> by pay order,
                banker&rsquo;s cheque or demand draft in favour of Chairman ETPB.
            </p>
            <div class="auth-legal">
                The white band of the national flag stands for Pakistan&rsquo;s religious
                minorities. The properties administered through this system are held in trust
                for those communities.
            </div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <h1>Create an account</h1>
            <p class="lede">For applicants. Officers are issued credentials by their district office.</p>

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
                    <label for="name">Full name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="input @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required maxlength="150" autofocus>
                    @error('name')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="cnic">CNIC <span class="req">*</span></label>
                    <input type="text" id="cnic" name="cnic" class="input @error('cnic') is-invalid @enderror"
                           value="{{ old('cnic') }}" required inputmode="numeric"
                           pattern="[0-9]{13}" maxlength="13" placeholder="3520112345671">
                    <p class="hint">13 digits, without dashes. One account per CNIC.</p>
                    @error('cnic')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autocomplete="username">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="contact">Mobile number <span class="req">*</span></label>
                    <input type="tel" id="contact" name="contact" class="input @error('contact') is-invalid @enderror"
                           value="{{ old('contact') }}" required maxlength="20" placeholder="0300-1234567">
                    @error('contact')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password">Password <span class="req">*</span></label>
                    <input type="password" id="password" name="password"
                           class="input @error('password') is-invalid @enderror"
                           required autocomplete="new-password">
                    <p class="hint">At least 10 characters, with letters and numbers.</p>
                    @error('password')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password <span class="req">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="input" required autocomplete="new-password">
                </div>

                <div class="field" style="display:flex;align-items:flex-start;gap:.5rem">
                    <input type="checkbox" id="declaration" name="declaration" value="1"
                           style="margin:.3rem 0 0" required>
                    <label for="declaration" style="margin:0;font-weight:500;font-size:.85rem">
                        I confirm that the information I give will be true, and I understand that a
                        false statement may lead to my application being rejected.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
                    Create account and start an application
                </button>
            </form>

            <hr class="divider">

            <p style="font-size:.85rem;margin:0">
                Already registered? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </main>
</div>
</body>
</html>
