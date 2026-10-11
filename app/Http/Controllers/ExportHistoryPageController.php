<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders the Blade/Alpine shell for export history (spec §10.2/§10.4's
 * "Export History" nav item, spec §13). All data loads client-side
 * against /api/v1/exports and /api/v1/organizations/{org}/export.
 */
class ExportHistoryPageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('export', SurveyResponse::class);

        // Super Admin is org-less by design (User::isSuperAdmin()) and
        // bypasses every policy check via Gate::before, so the authorize()
        // call above always passes for them even though this page is
        // fundamentally "this organization's export history" — there's no
        // tenant here for them to view/trigger exports for. Aborting with
        // a clear message beats a null-organization crash further down.
        abort_if($request->user()->organization === null, 404, 'This page is only available within an organization.');

        return view('exports.index', ['organization' => $request->user()->organization]);
    }
}
