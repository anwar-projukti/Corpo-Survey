<?php

namespace App\Http\Resources\Surveys;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A field user's own view of an assigned survey (spec §7) — the same
 * question-tree shape PublicSurveyResource gives an anonymous respondent
 * (the door-to-door form reuses that same rendering logic), plus the
 * survey id itself, which the public flow gets from the signed URL/session
 * instead of needing in the payload.
 *
 * @mixin Survey
 */
class MySurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->currentPublishedVersion;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'version' => [
                'settings' => [
                    'one_question_per_screen' => $version->settingsData->one_question_per_screen,
                    'show_progress_bar' => $version->settingsData->show_progress_bar,
                ],
                'sections' => SurveySectionResource::collection($version->sections),
                'rules' => SurveyRuleResource::collection($version->rules),
            ],
        ];
    }
}
