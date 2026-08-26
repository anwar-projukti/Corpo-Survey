<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for response management (spec §8). All
 * actual data loading happens client-side against /api/v1/surveys/{survey}/responses
 * and /api/v1/responses/... — these views just need to exist, be
 * authorized, and know which survey (and, for the detail view, which
 * response) to load.
 */
class SurveyResponsePageController extends Controller
{
    public function index(Survey $survey): View
    {
        $this->authorize('viewAny', SurveyResponse::class);
        $this->authorize('view', $survey);

        return view('responses.index', ['survey' => $survey]);
    }

    public function show(Survey $survey, SurveyResponse $response): View
    {
        $this->authorize('view', $response);
        abort_unless($response->survey_id === $survey->id, 404);

        return view('responses.show', ['survey' => $survey, 'response' => $response]);
    }
}
