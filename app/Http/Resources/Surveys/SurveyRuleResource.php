<?php

namespace App\Http\Resources\Surveys;

use App\Models\SurveyRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyRule
 */
class SurveyRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'conditions' => $this->conditions,
            'action' => $this->action->value,
            'target' => $this->target,
            'order' => $this->order,
            'is_active' => $this->is_active,
        ];
    }
}
