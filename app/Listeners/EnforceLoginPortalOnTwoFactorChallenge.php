<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\LoginPortal;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;

/**
 * The 2FA challenge (TwoFactorAuthenticatedSessionController::store, in
 * laravel/fortify) authenticates the session immediately after this event
 * fires, with no pipeline hook of ours in between — unlike the password-only
 * path, which runs through EnsureUserMatchesLoginPortal before the session
 * is established. Throwing here, before that login() call, is the only way
 * to reject a 2FA-verified user who signed in through the wrong portal
 * (e.g. an Org Admin's password + code submitted on /admin/login).
 */
class EnforceLoginPortalOnTwoFactorChallenge
{
    public function handle(ValidTwoFactorAuthenticationCodeProvided $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $portal = (string) Session::get('login.portal', LoginPortal::USER);

        if (! LoginPortal::isValid($portal)) {
            $portal = LoginPortal::USER;
        }

        if (! LoginPortal::allows($portal, $user)) {
            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }
    }
}
