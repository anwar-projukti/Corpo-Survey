<?php

namespace Database\Factories;

use App\Models\SurveySection;
use App\Models\SurveyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveySection>
 */
class SurveySectionFactory extends Factory
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
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'order' => 0,
        ];
    }
}
