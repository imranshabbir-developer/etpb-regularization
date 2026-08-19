<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard for a permission code.
 *
 * Usage: ->middleware('can.do:assessment.fix_rent')
 * Several codes may be given; holding any one of them is enough.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($permissions === [] || $user->hasAnyPermission(...$permissions)) {
            return $next($request);
        }

        abort(403, 'You do not hold the permission required for this action.');
    }
}
