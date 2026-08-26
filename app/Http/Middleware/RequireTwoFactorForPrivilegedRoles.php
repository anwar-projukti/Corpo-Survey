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
 *
 * Gated by config('two-factor.enforced') (env TWO_FACTOR_ENFORCED, default
 * true) purely as a local-development escape hatch — e.g. no authenticator
 * app handy, or the QR code's issuer field trips up a picky client like
 * Microsoft Authenticator. Never disable this in a real deployment; the
 * requirement itself is spec, not optional.
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
        'two-factor.setup.confirm-password',
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
        if (! config('two-factor.enforced')) {
            return $next($request);
        }

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

        // A JSON-expecting request (e.g. the theme switcher's fetch() call)
        // must never receive an HTML redirect — the caller can't follow it
        // meaningfully, and fetch() won't treat it as an error either, so
        // the failure would otherwise be silent. Every such endpoint would
        // otherwise need to be remembered and added to EXEMPT_ROUTES by
        // hand, which is exactly how this class of bug slips in.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication setup is required before this action.',
            ], 409);
        }

        return redirect()->route('two-factor.setup');
    }
}
