<?php

namespace App\Http\Resources\Surveys;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The respondent-facing shape of a published survey — deliberately
 * narrower than the admin SurveyResource: no created_by, no draft version,
 * no timestamps. Only what the public form needs to render itself.
 *
 * @mixin Survey
 */
class PublicSurveyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->currentPublishedVersion;

        return [
            'title' => $this->title,
            'description' => $this->description,
            'version' => [
                'settings' => [
                    'one_question_per_screen' => $version->settingsData->one_question_per_screen,
                    'show_progress_bar' => $version->settingsData->show_progress_bar,
                    'allow_save_and_resume' => $version->settingsData->allow_save_and_resume,
                    'require_otp' => $version->settingsData->require_otp,
                ],
                'sections' => SurveySectionResource::collection($version->sections),
                'rules' => SurveyRuleResource::collection($version->rules),
            ],
        ];
    }
}
