<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\PricingPlan;
use App\Support\LoginPortal;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public registration, kept separate from Fortify's own
 * RegisteredUserController (config/fortify.php disables
 * Features::registration() for exactly this reason): Fortify logs the new
 * user in immediately after creating their account, which skips the
 * sign-in step. Here the account is created the same way (via
 * CreateNewUser, so validation/org-creation is identical) but the session
 * is never started — the user is sent to /login to sign in themselves.
 */
class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        // Purely a display cue carried from the landing page's pricing
        // cards ("Get started" on the Basic plan links here with
        // ?plan=<id>) — there's no subscription/billing system yet tying
        // a registration to a plan, so an invalid or missing id just
        // means no plan banner shows rather than a 404. old('plan') covers
        // the redirect-back-with-errors case: the form resubmits the id as
        // a hidden field so the banner survives a failed submission, where
        // the query string itself is long gone.
        $selectedPlan = PricingPlan::query()->find($request->query('plan', $request->old('plan')));

        return view('auth.register', ['selectedPlan' => $selectedPlan]);
    }

    public function store(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $user = $creator->create($request->all());

        event(new Registered($user));

        // A freshly registered user is always an Org Admin (see
        // CreateNewUser), which EnsureUserMatchesLoginPortal only accepts
        // through /org/login, not the plain /login this route used to send
        // them to — a correct password there still failed with "these
        // credentials do not match our records" because the portal, not
        // the password, was wrong.
        $portal = LoginPortal::for($user);

        return redirect()
            ->route(LoginPortal::loginRouteFor($portal))
            ->with('status', __('messages.auth.registered_please_login'));
    }
}
