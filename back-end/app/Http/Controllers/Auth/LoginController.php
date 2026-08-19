<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sign-in.
 *
 * Every attempt — successful or not — is written to `login_attempts`, and
 * repeated failures lock the account. This is a land-records system whose
 * decisions are challengeable in court, so who touched it and when is part
 * of the record.
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;      // 15 minutes
    private const LOCK_THRESHOLD = 8;

    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function attempt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email', 'max:191'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'official email',
        ]);

        $throttleKey = mb_strtolower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->record($request, $data['email'], false, 'throttled');

            throw ValidationException::withMessages([
                'email' => sprintf(
                    'Too many sign-in attempts. Please try again in %d minute(s).',
                    max(1, (int) ceil($seconds / 60))
                ),
            ]);
        }

        $user = User::where('email', $data['email'])->first();

        if ($user && $user->isLocked()) {
            $this->record($request, $data['email'], false, 'account_locked');

            throw ValidationException::withMessages([
                'email' => 'This account is locked. Please contact the system administrator.',
            ]);
        }

        if ($user && $user->status !== 'ACTIVE') {
            $this->record($request, $data['email'], false, 'account_' . mb_strtolower($user->status));

            throw ValidationException::withMessages([
                'email' => 'This account is not active. Please contact the system administrator.',
            ]);
        }

        if (! Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            $this->record($request, $data['email'], false, 'bad_credentials');
            $this->registerFailure($user);

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = Auth::user();
        $user->forceFill([
            'last_login_at'      => now(),
            'last_login_ip'      => $request->ip(),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ])->save();
        $user->flushAuthorisationCache();

        $this->record($request, $data['email'], true, null);

        if ($user->force_password_change) {
            return redirect()->route('password.change')
                ->with('warning', 'Your password must be changed before you can continue.');
        }

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Signed in as ' . $user->name . '.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user?->flushAuthorisationCache();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }

    private function registerFailure(?User $user): void
    {
        if (! $user) {
            return;
        }

        $count = $user->failed_login_count + 1;
        $attrs = ['failed_login_count' => $count];

        if ($count >= self::LOCK_THRESHOLD) {
            $attrs['locked_until'] = now()->addHour();
        }

        $user->forceFill($attrs)->save();
    }

    private function record(Request $request, string $identifier, bool $ok, ?string $reason): void
    {
        DB::table('login_attempts')->insert([
            'identifier'     => mb_substr($identifier, 0, 191),
            'ip_address'     => (string) $request->ip(),
            'user_agent'     => mb_substr((string) $request->userAgent(), 0, 255),
            'successful'     => $ok,
            'failure_reason' => $reason,
            'attempted_at'   => now(),
        ]);
    }
}
