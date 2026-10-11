<?php

namespace App\Http\Resources\Exports;

use App\Models\UserSurveyStat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserSurveyStat
 */
class UserSurveyStatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'survey_id' => $this->survey_id,
            'survey_title' => $this->whenLoaded('survey', fn () => $this->survey->title),
            'response_count' => $this->response_count,
            'last_submitted_at' => $this->last_submitted_at?->toIso8601String(),
        ];
    }
}
