<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Public self-registration.
 *
 * The scheme is addressed to the general public, so an occupant must be able to
 * reach the portal without going through an office first. A member of the
 * public registers here and gets the APPLICANT role and nothing else — they can
 * file and track their own application and see no one else's.
 *
 * The account is keyed to a CNIC, which is unique per person, so one person
 * cannot quietly accumulate several identities on the same property.
 */
class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'cnic'     => ['required', 'digits:13', 'unique:users,cnic'],
            'email'    => ['required', 'email', 'max:191', 'unique:users,email'],
            'contact'  => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'declaration' => ['accepted'],
        ], [
            'cnic.digits'      => 'The CNIC must be exactly 13 digits, without dashes.',
            'cnic.unique'      => 'An account already exists for this CNIC. Sign in instead, or contact your district office.',
            'email.unique'     => 'An account already exists for this email address.',
            'declaration.accepted' => 'You must confirm that the information you give will be true.',
        ]);

        $role = Role::where('code', 'APPLICANT')->firstOrFail();

        $user = DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name'                  => $data['name'],
                'email'                 => $data['email'],
                'cnic'                  => $data['cnic'],
                'contact'               => $data['contact'],
                'password'              => $data['password'],
                'status'                => 'ACTIVE',
                'designation'           => 'Applicant',
                // The applicant chose this password themselves, so there is
                // nothing to force a change of.
                'force_password_change' => false,
                'password_changed_at'   => now(),
            ]);

            DB::table('user_role')->insert([
                'user_id'     => $user->id,
                'role_id'     => $role->id,
                'assigned_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('applications.create')->with(
            'status',
            'Welcome. Fill in the six sections, then record your Rs. 5,000 deposit — '
            . 'the department begins processing once the deposit is confirmed.',
        );
    }
}
