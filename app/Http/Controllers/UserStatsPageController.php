<?php

namespace App\Http\Controllers;

use App\Models\UserSurveyStat;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders the Blade/Alpine shell for the org-wide user productivity
 * report (spec §10.3). All data loads client-side against
 * /api/v1/organizations/{org}/user-stats.
 */
class UserStatsPageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', UserSurveyStat::class);

        // Super Admin is org-less by design (User::isSuperAdmin()) and
        // bypasses every policy check via Gate::before, so the authorize()
        // call above always passes for them even though this page is
        // fundamentally "this organization's productivity report" —
        // there's no tenant here for them to view. Aborting with a clear
        // message beats a null-organization crash further down.
        abort_if($request->user()->organization === null, 404, 'This page is only available within an organization.');

        return view('user-stats.index', ['organization' => $request->user()->organization]);
    }
}
