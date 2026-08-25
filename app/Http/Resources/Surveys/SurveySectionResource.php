<?php

namespace App\Http\Resources\Surveys;

use App\Models\SurveySection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveySection
 */
class SurveySectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'questions' => SurveyQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
