<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders the Blade/Alpine shell for the respondent import wizard (spec
 * §10.4). All data loads client-side against /api/v1/imports/...
 */
class RespondentImportPageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('import', SurveyResponse::class);

        // Super Admin is org-less by design (User::isSuperAdmin()) and
        // bypasses every policy check via Gate::before, so the authorize()
        // call above always passes for them even though this page is
        // fundamentally "import respondents into this organization" —
        // there's no tenant here for them to import into. Aborting with a
        // clear message beats a null-organization crash further down,
        // matching ExportHistoryPageController/UserStatsPageController.
        abort_if($request->user()->organization === null, 404, 'This page is only available within an organization.');

        return view('imports.index');
    }
}
