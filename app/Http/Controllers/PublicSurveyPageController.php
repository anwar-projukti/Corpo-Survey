<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for the respondent-facing public survey
 * page (spec §5.8). Structure and submission both happen client-side
 * against /api/v1/public/surveys/{survey} — this view only needs to exist
 * and know which survey to load. Reaching this action at all already means
 * the request passed the "signed" middleware's signature check.
 */
class PublicSurveyPageController extends Controller
{
    public function show(Survey $survey): View
    {
        return view('surveys.public', ['survey' => $survey]);
    }
}
