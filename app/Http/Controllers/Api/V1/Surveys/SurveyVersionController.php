<?php

namespace App\Http\Controllers\Api\V1\Surveys;

use App\Http\Controllers\Controller;
use App\Http\Resources\Surveys\SurveyVersionResource;
use App\Models\SurveyVersion;

class SurveyVersionController extends Controller
{
    public function show(SurveyVersion $version): SurveyVersionResource
    {
        $this->authorize('view', $version);

        return new SurveyVersionResource($version->load(['sections.questions.options', 'rules']));
    }
}
