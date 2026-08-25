<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Org Admin and Super Admin must have 2FA enabled and confirmed (spec §5.1).
 * Users in those roles are redirected to the 2FA setup screen until they
 * complete it — every other route stays reachable so the redirect itself
 * doesn't create a lockout.
 */
class RequireTwoFactorForPrivilegedRoles
{
    private const PRIVILEGED_ROLES = ['Org Admin', 'Super Admin'];

    /**
     * Routes that must stay reachable while 2FA is unconfirmed. This
     * includes password.confirm(.store) — Fortify's own two-factor.enable/
     * confirm/qr-code routes require a freshly confirmed password (see
     * config/fortify.php's confirmPassword option) and redirect there, so
     * omitting it would create a redirect loop back to two-factor.setup.
     */
    private const EXEMPT_ROUTES = [
        'two-factor.setup',
        'two-factor.login',
        'two-factor.login.store',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'user-profile-information.update',
        'logout',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(self::PRIVILEGED_ROLES)) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs(self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup');
    }
}
