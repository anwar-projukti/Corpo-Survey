<?php

namespace App\Http\Resources\Responses;

use App\Models\ResponseAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ResponseAnswer
 */
class ResponseAnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'survey_question_id' => $this->survey_question_id,
            'value' => $this->value,
        ];
    }
}
