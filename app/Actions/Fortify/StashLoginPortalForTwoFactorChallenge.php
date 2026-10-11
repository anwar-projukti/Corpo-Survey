<?php

namespace App\Actions\Fortify;

use App\Support\LoginPortal;
use Illuminate\Http\Request;

/**
 * Fortify's own RedirectIfTwoFactorAuthenticatable — which runs right after
 * this stage — sends 2FA-enabled accounts to the two-factor challenge
 * screen before AttemptToAuthenticate (and so before
 * EnsureUserMatchesLoginPortal) ever runs. The portal the login form was
 * submitted from has to survive that redirect so
 * App\Listeners\EnforceLoginPortalOnTwoFactorChallenge can still enforce it
 * once the 2FA code is verified — hence stashing it in the session
 * alongside Fortify's own login.id/login.remember.
 */
class StashLoginPortalForTwoFactorChallenge
{
    public function handle(Request $request, callable $next): mixed
    {
        $portal = (string) $request->input('portal', LoginPortal::USER);

        $request->session()->put('login.portal', LoginPortal::isValid($portal) ? $portal : LoginPortal::USER);

        return $next($request);
    }
}
