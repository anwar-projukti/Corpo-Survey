<?php

namespace App\Http\Controllers\Api\V1\Responses;

use App\Actions\Responses\ReviewSurveyResponse;
use App\Enums\ResponseReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Responses\ReviewResponseRequest;
use App\Http\Resources\Responses\SurveyResponseResource;
use App\Models\SurveyResponse;

class ResponseReviewController extends Controller
{
    public function store(ReviewResponseRequest $request, SurveyResponse $response, ReviewSurveyResponse $reviewSurveyResponse): SurveyResponseResource
    {
        $updated = $reviewSurveyResponse->handle(
            $response,
            $request->enum('review_status', ResponseReviewStatus::class),
            $request->user(),
        );

        return new SurveyResponseResource($updated);
    }
}
