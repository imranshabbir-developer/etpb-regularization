<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A seeded or reset password cannot survive first use: until it is changed,
 * every route except the change-password screen itself redirects there.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->force_password_change
            && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
