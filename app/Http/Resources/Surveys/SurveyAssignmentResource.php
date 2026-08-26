<?php

namespace App\Http\Resources\Surveys;

use App\Http\Resources\Users\UserSummaryResource;
use App\Models\SurveyAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyAssignment
 */
class SurveyAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'assigned_by' => new UserSummaryResource($this->whenLoaded('assignedBy')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
