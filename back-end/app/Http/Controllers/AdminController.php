<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\DocumentType;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * Administration: users, reference data, statutory settings and the audit log.
 *
 * Two things here are deliberately constrained rather than free-form:
 *
 *  - a statutory setting is never edited in place. Changing one writes a new
 *    dated row and closes the old one, so a historic assessment can still be
 *    recomputed under the rules that actually applied to it.
 *  - the audit log is read-only. There is no route that writes to it from here
 *    and none that deletes from it, which is the point of having one.
 */
class AdminController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
    ) {
    }

    // ---- users ---------------------------------------------------------------

    public function users(Request $request): View
    {
        $users = User::query()
            ->with(['roles:id,code,name', 'district:id,name', 'office:id,name'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->string('q')->toString());
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('cnic', 'like', "%{$term}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles',
                fn ($r) => $r->where('code', $request->string('role'))))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', [
            'users'     => $users,
            'roles'     => Role::orderBy('hierarchy_level')->get(),
            'districts' => District::orderBy('name')->get(['id', 'name']),
            'offices'   => Office::orderBy('name')->get(['id', 'name']),
            'filters'   => $request->only('q', 'role'),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'email'       => ['required', 'email', 'max:191', 'unique:users,email'],
            'cnic'        => ['nullable', 'digits:13', 'unique:users,cnic'],
            'designation' => ['nullable', 'string', 'max:120'],
            'contact'     => ['nullable', 'string', 'max:20'],
            'role_id'     => ['required', 'integer', 'exists:roles,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'office_id'   => ['nullable', 'integer', 'exists:offices,id'],
        ]);

        // A one-time password the officer must change at first sign-in; it is
        // shown once, here, and never stored in readable form.
        $temporary = Str::password(14);

        DB::transaction(function () use ($data, $temporary) {
            $user = User::create([
                'name'                  => $data['name'],
                'email'                 => $data['email'],
                'cnic'                  => $data['cnic'] ?? null,
                'designation'           => $data['designation'] ?? null,
                'contact'               => $data['contact'] ?? null,
                'district_id'           => $data['district_id'] ?? null,
                'office_id'             => $data['office_id'] ?? null,
                'password'              => $temporary,
                'status'                => 'ACTIVE',
                'force_password_change' => true,
                'email_verified_at'     => now(),
            ]);

            DB::table('user_role')->insert([
                'user_id'     => $user->id,
                'role_id'     => $data['role_id'],
                'assigned_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        return back()->with('status', sprintf(
            'User created. Give them this one-time password, which they must change at first sign-in: %s',
            $temporary,
        ));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['SUSPEND', 'ACTIVATE', 'RESET_PASSWORD', 'UNLOCK'])],
        ]);

        // Locking yourself out helps nobody.
        if ($user->id === $request->user()->id && $data['action'] === 'SUSPEND') {
            return back()->with('error', 'You cannot suspend your own account.');
        }

        $message = '';

        switch ($data['action']) {
            case 'SUSPEND':
                $user->forceFill(['status' => 'SUSPENDED'])->save();
                $message = 'Account suspended.';
                break;

            case 'ACTIVATE':
                $user->forceFill([
                    'status'             => 'ACTIVE',
                    'locked_until'       => null,
                    'failed_login_count' => 0,
                ])->save();
                $message = 'Account activated.';
                break;

            case 'UNLOCK':
                $user->forceFill(['locked_until' => null, 'failed_login_count' => 0])->save();
                $message = 'Account unlocked.';
                break;

            case 'RESET_PASSWORD':
                $message = $this->resetPassword($user);
                break;
        }

        $user->flushAuthorisationCache();

        return back()->with('status', $message);
    }

    private function resetPassword(User $user): string
    {
        $temporary = Str::password(14);

        $user->forceFill([
            'password'              => Hash::make($temporary),
            'force_password_change' => true,
            'password_changed_at'   => null,
            'locked_until'          => null,
            'failed_login_count'    => 0,
        ])->save();

        return 'Password reset. Give them this one-time password: ' . $temporary;
    }

    // ---- reference data -------------------------------------------------------

    public function masters(): View
    {
        return view('admin.masters', [
            'provinces'   => DB::table('provinces')->orderBy('name')->get(),
            'districts'   => District::with(['province:id,name', 'unitProfile:id,code,name'])
                                ->orderBy('name')->paginate(30),
            'profiles'    => DB::table('unit_conversion_profiles')->orderByDesc('is_default')->get(),
            'factors'     => DB::table('unit_conversion_factors')
                                ->orderBy('unit_profile_id')->orderBy('display_order')->get()
                                ->groupBy('unit_profile_id'),
            'documentTypes' => DocumentType::orderBy('display_order')->get(),
            'rateSources' => DB::table('rate_sources')->orderBy('display_order')->get(),
            'counts'      => [
                'provinces' => DB::table('provinces')->count(),
                'divisions' => DB::table('divisions')->count(),
                'districts' => DB::table('districts')->count(),
                'tehsils'   => DB::table('tehsils')->count(),
                'mouzas'    => DB::table('mouzas')->count(),
                'offices'   => DB::table('offices')->count(),
            ],
        ]);
    }

    /** The one master an administrator genuinely needs to change: which Marla applies. */
    public function updateDistrictProfile(Request $request, District $district): RedirectResponse
    {
        $data = $request->validate([
            'unit_profile_id' => ['required', 'integer', 'exists:unit_conversion_profiles,id'],
        ]);

        $district->forceFill($data)->save();

        return back()->with('status', sprintf(
            'The measurement standard for %s has been changed. Applications already assessed keep '
            . 'the factors frozen against them and are unaffected.',
            $district->name,
        ));
    }

    // ---- statutory settings ---------------------------------------------------

    public function settings(): View
    {
        $groups = ['eligibility', 'arrears', 'assessment', 'due_process', 'approval',
                   'fee', 'enforcement', 'reporting', 'measurement', 'general'];

        $rows = [];
        foreach ($groups as $group) {
            $found = $this->settings->group($group);
            if ($found !== []) {
                $rows[$group] = $found;
            }
        }

        return view('admin.settings', [
            'groups'  => $rows,
            'history' => DB::table('settings')->whereNotNull('effective_to')
                            ->orderByDesc('effective_from')->limit(30)->get(),
        ]);
    }

    public function updateSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'            => ['required', 'string', 'max:80'],
            'value'          => ['required', 'string', 'max:2000'],
            'effective_from' => ['required', 'date'],
        ]);

        try {
            $this->settings->supersede(
                $data['key'], $data['value'], $data['effective_from'], $request->user()->id,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', sprintf(
            'Setting "%s" superseded from %s. The previous value is preserved, so assessments '
            . 'made under it can still be reproduced.',
            $data['key'],
            $data['effective_from'],
        ));
    }

    // ---- audit ---------------------------------------------------------------

    public function audit(Request $request): View
    {
        $logs = DB::table('audit_logs')
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit', [
            'logs'    => $logs,
            'events'  => DB::table('audit_logs')->select('event')->distinct()->pluck('event'),
            'users'   => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('event', 'user'),
            'logins'  => DB::table('login_attempts')->orderByDesc('attempted_at')->limit(25)->get(),
        ]);
    }
}
