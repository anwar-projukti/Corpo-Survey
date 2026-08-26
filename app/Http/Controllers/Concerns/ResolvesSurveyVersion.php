<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Survey;
use App\Models\SurveyVersion;
use Illuminate\Http\Request;

/**
 * Shared by every controller that operates on "one survey version at a
 * time" (spec §6.2 — each version can have its own question set, so
 * responses/filters/analytics only make sense against one specific
 * version's own question ids): the response table
 * (App\Http\Controllers\Api\V1\Responses\SurveyResponseController) and
 * analytics (App\Http\Controllers\Api\V1\Analytics\SurveyAnalyticsController).
 * Defaults to the currently published version, since that's the one
 * actually collecting responses; falls back to the draft only when
 * nothing has ever been published.
 */
trait ResolvesSurveyVersion
{
    private function resolveVersion(Request $request, Survey $survey): SurveyVersion
    {
        if ($request->filled('version_id')) {
            return SurveyVersion::where('survey_id', $survey->id)->findOrFail($request->integer('version_id'));
        }

        $version = $survey->currentPublishedVersion ?? $survey->currentDraftVersion;

        abort_if($version === null, 404, 'This survey has no version to show data for.');

        return $version;
    }
}
