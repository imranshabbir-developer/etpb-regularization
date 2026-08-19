<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Permission gate: @can('do', 'assessment.fix_rent') in Blade,
        // Gate::allows('do', $code) in code. Backed by role_permission.
        Gate::define('do', static fn (User $user, string $permission) => $user->hasPermission($permission));
    }
}
