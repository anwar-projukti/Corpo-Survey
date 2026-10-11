<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for the survey builder pages. All actual
 * data loading happens client-side against /api/v1/surveys/... — these
 * views just need to exist, be authorized, and know which survey to load.
 *
 * A Field User holds survey.view (SurveyPolicy::view/viewAny pass) so the
 * JSON API stays open to them for whatever client context needs it, but
 * this builder screen is a strictly different concern: it's for building
 * and managing a survey, and a Field User never does that — they only
 * ever fill one in via /my/surveys once assigned (spec §7). Blocking by
 * role here, not by narrowing the shared survey.view permission, so
 * Viewer (survey.view without any field-collection role) keeps its
 * read-only browse of this same page.
 *
 * index() is also blocked for Super Admin (spec §13 "System Admin"):
 * they manage organizations, not survey content, so there's no cross-org
 * survey list for them to browse. show() stays open to them (via
 * Gate::before) so they can still open one organization's survey
 * directly for support purposes.
 */
class SurveyPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Survey::class);
        abort_if(auth()->user()->isFieldUserOnly() || auth()->user()->isSuperAdmin(), 403);

        return view('surveys.index');
    }

    public function show(Survey $survey): View
    {
        $this->authorize('view', $survey);
        abort_if(auth()->user()->isFieldUserOnly(), 403);

        return view('surveys.builder', ['survey' => $survey]);
    }
}
