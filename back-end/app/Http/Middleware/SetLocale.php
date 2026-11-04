<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen interface language.
 *
 * The choice lives in the session so it survives sign-in and sign-out; a
 * visitor who has never chosen gets the configured default (Urdu).
 */
class SetLocale
{
    public const SUPPORTED = ['ur', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }

    public static function isRtl(?string $locale = null): bool
    {
        return ($locale ?? App::getLocale()) === 'ur';
    }
}
