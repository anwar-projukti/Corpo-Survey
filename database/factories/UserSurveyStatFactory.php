<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\User;
use App\Models\UserSurveyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSurveyStat>
 */
class UserSurveyStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'survey_id' => Survey::factory(),
            'response_count' => fake()->numberBetween(1, 100),
            'last_submitted_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }
}
