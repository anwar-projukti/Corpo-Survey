<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a Super Admin log in as an organization's Org Admin for support
 * purposes (spec §13 "System Admin"), without knowing that account's
 * password. The Super Admin's own id is stashed in the session
 * (impersonator_id) rather than actually ending their session, so
 * destroy() can restore it — and RequireTwoFactorForPrivilegedRoles
 * checks that key to skip the impersonated account's own 2FA requirement,
 * since the Super Admin already cleared that bar to get here.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('viewAny', Organization::class);

        abort_if($request->session()->has('impersonator_id'), 409, 'Already impersonating — return to System Admin first.');

        $target = $organization->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Org Admin'))
            ->first();

        abort_if($target === null, 404, 'This organization has no Org Admin to impersonate.');

        $realUser = $request->user();

        app(AuditLogger::class)->log('organization.impersonation_started', subject: $organization, properties: [
            'impersonated_user_id' => $target->id,
            'impersonated_user_email' => $target->email,
        ], actor: $realUser);

        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $realUser->id);
        $this->swapAuthenticatedUser($request, $target);

        return redirect()->route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        abort_if($impersonatorId === null, 409, 'Not currently impersonating.');

        $impersonatedUser = $request->user();
        $realUser = User::findOrFail($impersonatorId);

        app(AuditLogger::class)->log('organization.impersonation_ended', subject: $impersonatedUser->organization, properties: [
            'impersonated_user_id' => $impersonatedUser->id,
            'impersonated_user_email' => $impersonatedUser->email,
        ], actor: $realUser);

        $request->session()->regenerate();
        $request->session()->forget('impersonator_id');
        $this->swapAuthenticatedUser($request, $realUser);

        return redirect()->route('organizations.index');
    }

    /**
     * Swaps the session's authenticated user without going through
     * Auth::login()'s normal path — that fires the Login event, which
     * would make App\Listeners\LogSuccessfulLogin stamp last_login_at/ip
     * on the impersonated account and reset auth.session_started_at,
     * both wrong here: this isn't a real login by that account, and the
     * absolute session-lifetime clock (EnsureSessionIsNotExpired) should
     * keep counting from the Super Admin's own original login.
     */
    private function swapAuthenticatedUser(Request $request, User $user): void
    {
        $guard = Auth::guard(config('fortify.guard'));

        $request->session()->put($guard->getName(), $user->getAuthIdentifier());
        $guard->setUser($user);
    }
}
