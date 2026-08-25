<?php

namespace App\Http\Resources\Surveys;

use App\Models\SurveyVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyVersion
 */
class SurveyVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version_number' => $this->version_number,
            'status' => $this->status->value,
            'settings' => $this->settingsData,
            'published_by' => $this->published_by,
            'published_at' => $this->published_at?->toIso8601String(),
            'sections' => SurveySectionResource::collection($this->whenLoaded('sections')),
            'rules' => SurveyRuleResource::collection($this->whenLoaded('rules')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
