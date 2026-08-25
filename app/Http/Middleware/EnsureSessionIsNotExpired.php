<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces an absolute session lifetime on top of Laravel's built-in idle
 * timeout (SESSION_LIFETIME, driven by last_activity). A session started
 * more than SESSION_ABSOLUTE_LIFETIME minutes ago is force-logged-out even
 * if the user has been continuously active.
 *
 * auth.session_started_at is normally stamped by
 * App\Listeners\LogSuccessfulLogin at actual login time, so the real login
 * flow always measures from the true start. The fallback below only
 * matters for a session that reaches this middleware without ever going
 * through that listener (a session that predates this feature, or a test
 * using actingAs()) — such a session gets a fresh window starting now
 * rather than being force-expired, which would otherwise make actingAs()
 * unusable in tests. Laravel's own idle timeout still bounds it either way.
 */
class EnsureSessionIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $startedAt = $request->session()->get('auth.session_started_at');

            if ($startedAt === null) {
                $request->session()->put('auth.session_started_at', now()->timestamp);
            } elseif (now()->timestamp - $startedAt > $this->absoluteLifetimeSeconds()) {
                $this->logout($request);

                throw new AuthenticationException('Your session has expired. Please log in again.');
            }
        }

        return $next($request);
    }

    protected function absoluteLifetimeSeconds(): int
    {
        return (int) config('session.absolute_lifetime', 480) * 60;
    }

    protected function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
