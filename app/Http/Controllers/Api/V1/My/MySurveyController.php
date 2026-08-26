<?php

namespace App\Http\Controllers\Api\V1\My;

use App\Http\Controllers\Controller;
use App\Http\Resources\Surveys\MySurveyResource;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * A field user's own scoped view of surveys (spec §7) — only ever surveys
 * assigned to them via survey_assignments, enforced by the query itself
 * (index) or an explicit membership check (show), never by hiding rows
 * client-side. Deliberately not the same controller as the admin
 * SurveyController — the access pattern (assigned-only, self-scoped, no
 * survey.view permission needed) is different enough to warrant its own.
 */
class MySurveyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $surveys = $request->user()->assignedSurveys()
            ->whereNotNull('current_published_version_id')
            ->with('currentPublishedVersion.sections.questions.options', 'currentPublishedVersion.rules')
            ->get();

        return MySurveyResource::collection($surveys);
    }

    public function show(Request $request, Survey $survey): MySurveyResource|JsonResponse
    {
        if (! $request->user()->assignedSurveys()->where('surveys.id', $survey->id)->exists()) {
            abort(403, 'This survey is not assigned to you.');
        }

        if (! $survey->currentPublishedVersion) {
            return response()->json(['message' => 'This survey has no published version.'], 404);
        }

        $survey->load('currentPublishedVersion.sections.questions.options', 'currentPublishedVersion.rules');

        return new MySurveyResource($survey);
    }
}
