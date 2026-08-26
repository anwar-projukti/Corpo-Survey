<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

/**
 * Fortify's own password-confirmation flow (RequirePassword middleware)
 * redirects back to whatever Laravel's redirect()->intended() finds in
 * session('url.intended') — which is only ever populated automatically by
 * the Authenticate middleware's own redirect, not by an ordinary browser
 * navigation. The two-factor setup page reaches /user/confirm-password via
 * a plain client-side redirect (its own axios call got a 423 from
 * RequirePassword), so nothing has set that session value — this route
 * sets it explicitly before handing off to Fortify's confirm-password
 * screen, so a successful confirmation lands back on two-factor.setup
 * instead of Fortify's generic post-confirmation default.
 */
class ConfirmPasswordForTwoFactorController extends Controller
{
    public function __invoke(Redirector $redirector): RedirectResponse
    {
        $redirector->setIntendedUrl(route('two-factor.setup'));

        return $redirector->to(route('password.confirm'));
    }
}
