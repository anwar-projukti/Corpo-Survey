<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Renders the Blade/Alpine shell for the door-to-door field collection
 * portal (spec §7). All actual data loading happens client-side against
 * /api/v1/my/... — these views just need to exist and be authorized. Not
 * gated by SurveyPolicy's ordinary "view" ability (that's about seeing a
 * survey in the admin builder) — a Field User may only ever open a survey
 * here that they're explicitly assigned to, checked the same way the
 * /api/v1/my/... endpoints check it.
 */
class MyFieldSurveyPageController extends Controller
{
    public function index(): View
    {
        return view('field.surveys-index');
    }

    public function show(Survey $survey): View
    {
        if (! auth()->user()->assignedSurveys()->where('surveys.id', $survey->id)->exists()) {
            abort(403, 'This survey is not assigned to you.');
        }

        return view('field.survey-form', ['survey' => $survey]);
    }
}
