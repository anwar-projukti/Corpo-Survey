<?php

namespace App\Http\Resources\Surveys;

use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

/**
 * @mixin SurveyQuestion
 */
class SurveyQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'label' => $this->label,
            'help_text' => $this->help_text,
            'config' => $this->config ?? new stdClass,
            'is_required' => $this->is_required,
            'order' => $this->order,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
