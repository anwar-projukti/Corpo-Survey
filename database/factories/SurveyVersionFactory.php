<?php

namespace Database\Factories;

use App\Enums\SurveyVersionStatus;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyVersion>
 */
class SurveyVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'version_number' => 1,
            'status' => SurveyVersionStatus::Draft,
            'settings' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SurveyVersionStatus::Published,
            'published_by' => User::factory(),
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SurveyVersionStatus::Archived,
        ]);
    }
}
