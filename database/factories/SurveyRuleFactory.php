<?php

namespace Database\Factories;

use App\Enums\RuleAction;
use App\Models\SurveyRule;
use App\Models\SurveyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyRule>
 */
class SurveyRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_version_id' => SurveyVersion::factory(),
            'name' => fake()->sentence(3),
            'conditions' => [
                'question_id' => 1,
                'operator' => 'equals',
                'value' => 'yes',
            ],
            'action' => RuleAction::Hide,
            'target' => ['question_id' => 2],
            'order' => 0,
            'is_active' => true,
        ];
    }
}
