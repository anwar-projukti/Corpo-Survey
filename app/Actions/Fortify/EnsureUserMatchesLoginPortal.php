<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\LoginPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Runs immediately after Fortify's AttemptToAuthenticate in the login
 * pipeline (see FortifyServiceProvider::authenticateThrough). A correct
 * password is not enough on its own: the account must also hold a role
 * allowed through the portal the form was submitted from (spec: separate
 * login URLs for Super Admin, Org Admin, and everyone else), otherwise the
 * session is torn down and the attempt fails the same way a bad password
 * would.
 */
class EnsureUserMatchesLoginPortal
{
    public function handle(Request $request, callable $next): mixed
    {
        $portal = (string) $request->input('portal', LoginPortal::USER);

        if (! LoginPortal::isValid($portal)) {
            $portal = LoginPortal::USER;
        }

        /** @var User $user */
        $user = Auth::user();

        if (! LoginPortal::allows($portal, $user)) {
            Auth::guard(config('fortify.guard'))->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        return $next($request);
    }
}
