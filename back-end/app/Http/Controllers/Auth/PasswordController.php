<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ], [
            'password.confirmed' => 'The new password and its confirmation do not match.',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'The new password must differ from the current one.']);
        }

        $user->forceFill([
            'password'              => $data['password'],
            'force_password_change' => false,
            'password_changed_at'   => now(),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Your password has been changed.');
    }
}
