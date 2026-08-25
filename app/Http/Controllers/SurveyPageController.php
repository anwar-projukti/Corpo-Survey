<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for the survey builder pages. All actual
 * data loading happens client-side against /api/v1/surveys/... — these
 * views just need to exist, be authorized, and know which survey to load.
 */
class SurveyPageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Survey::class);

        return view('surveys.index');
    }

    public function show(Survey $survey): View
    {
        $this->authorize('view', $survey);

        return view('surveys.builder', ['survey' => $survey]);
    }
}
