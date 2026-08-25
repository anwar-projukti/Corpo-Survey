<?php

namespace App\Http\Controllers\Api\V1\PublicSurveys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Surveys\PublicSurveyResource;
use App\Models\Survey;
use App\Services\PublicSurveyAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSurveyController extends Controller
{
    public function show(Request $request, Survey $survey, PublicSurveyAccess $access): JsonResponse
    {
        $version = $survey->currentPublishedVersion;

        if (! $version) {
            return response()->json(['message' => __('surveys.public.closed_body')], 404);
        }

        $survey->setRelation('currentPublishedVersion', $version->load(['sections.questions.options', 'rules']));

        $fingerprint = $access->fingerprint($request);

        if ($access->hasReachedDuplicateLimit($version, $fingerprint)) {
            return response()->json(['message' => __('surveys.public.duplicate_body')], 409);
        }

        $response = (new PublicSurveyResource($survey))->response();

        return $access->withRespondentCookie($request, $response);
    }
}
