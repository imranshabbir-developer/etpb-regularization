<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'password_changed_at'   => 'datetime',
            'last_login_at'         => 'datetime',
            'locked_until'          => 'datetime',
            'force_password_change' => 'boolean',
            'two_factor_enabled'    => 'boolean',
        ];
    }

    // ---- relations ----------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')->withTimestamps();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    // ---- authorisation -------------------------------------------------

    /**
     * Permission codes granted through this user's roles.
     *
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        return Cache::remember(
            "user:{$this->id}:permissions",
            300,
            fn () => Permission::query()
                ->join('role_permission as rp', 'rp.permission_id', '=', 'permissions.id')
                ->join('user_role as ur', 'ur.role_id', '=', 'rp.role_id')
                ->where('ur.user_id', $this->id)
                ->distinct()
                ->pluck('permissions.code')
                ->all()
        );
    }

    public function hasPermission(string $code): bool
    {
        return in_array($code, $this->permissionCodes(), true);
    }

    /** True if the user holds any one of the given permissions. */
    public function hasAnyPermission(string ...$codes): bool
    {
        $held = $this->permissionCodes();

        foreach ($codes as $code) {
            if (in_array($code, $held, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public function roleCodes(): array
    {
        return Cache::remember(
            "user:{$this->id}:roles",
            300,
            fn () => $this->roles()->pluck('code')->all()
        );
    }

    public function hasRole(string ...$codes): bool
    {
        return array_intersect($codes, $this->roleCodes()) !== [];
    }

    public function isApplicant(): bool
    {
        return $this->hasRole('APPLICANT');
    }

    /** The highest authority the user holds; lower number is more senior. */
    public function seniority(): int
    {
        return (int) ($this->roles()->min('hierarchy_level') ?? 999);
    }

    /** Primary role, used for display and for stamping the audit trail. */
    public function primaryRole(): ?Role
    {
        return $this->roles()->orderBy('hierarchy_level')->first();
    }

    public function isLocked(): bool
    {
        return $this->status === 'LOCKED'
            || ($this->locked_until !== null && $this->locked_until->isFuture());
    }

    public function flushAuthorisationCache(): void
    {
        Cache::forget("user:{$this->id}:permissions");
        Cache::forget("user:{$this->id}:roles");
    }

    /** CNIC masked for display; the full value is a privileged view. */
    public function maskedCnic(): ?string
    {
        if (! $this->cnic || strlen($this->cnic) !== 13) {
            return $this->cnic;
        }

        return substr($this->cnic, 0, 5) . '-XXXXX-' . substr($this->cnic, -1);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}
