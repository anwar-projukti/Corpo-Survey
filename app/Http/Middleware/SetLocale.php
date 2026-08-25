<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for the request. A logged-in user's stored
 * preference (users.locale) always wins — it follows them across devices,
 * per spec §4. A guest's choice is remembered in the session only, since
 * there is no user record to persist it on.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        $supportedLocales = Locale::supported();

        if ($user && in_array($user->locale, $supportedLocales, true)) {
            return $user->locale;
        }

        $sessionLocale = $request->session()->get(self::SESSION_KEY);

        if (in_array($sessionLocale, $supportedLocales, true)) {
            return $sessionLocale;
        }

        return config('app.locale');
    }
}
