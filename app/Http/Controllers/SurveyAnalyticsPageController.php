<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for the analytics dashboard (spec §9).
 * All actual data loading happens client-side against
 * /api/v1/surveys/{survey}/analytics — this view just needs to exist,
 * be authorized, and know which survey to load.
 */
class SurveyAnalyticsPageController extends Controller
{
    public function show(Survey $survey): View
    {
        $this->authorize('viewReports', $survey);

        return view('analytics.show', ['survey' => $survey]);
    }
}
