<?php

namespace App\Http\Resources\Surveys;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Survey
 */
class SurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'is_published' => $this->isPublished(),
            'current_draft_version' => $this->whenLoaded(
                'currentDraftVersion',
                fn () => $this->currentDraftVersion ? new SurveyVersionResource($this->currentDraftVersion) : null,
            ),
            'current_published_version' => $this->whenLoaded(
                'currentPublishedVersion',
                fn () => $this->currentPublishedVersion ? new SurveyVersionResource($this->currentPublishedVersion) : null,
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
