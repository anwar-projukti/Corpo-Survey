<?php

namespace App\Http\Resources\Responses;

use App\Http\Resources\Users\UserSummaryResource;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serves both the response table (list, answers/notes omitted unless
 * eager-loaded) and the response detail view (full answers/notes/collector)
 * — one shape, driven by what the controller chose to load, rather than
 * two near-duplicate resource classes.
 *
 * @mixin SurveyResponse
 */
class SurveyResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'survey_version_id' => $this->survey_version_id,
            'status' => $this->status->value,
            'review_status' => $this->review_status->value,
            'collection_method' => $this->collection_method->value,
            'collector' => new UserSummaryResource($this->whenLoaded('collector')),
            'respondent_id' => $this->respondent_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'answers' => ResponseAnswerResource::collection($this->whenLoaded('answers')),
            'notes' => ResponseNoteResource::collection($this->whenLoaded('notes')),
        ];
    }
}
