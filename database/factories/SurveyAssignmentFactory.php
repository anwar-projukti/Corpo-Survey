<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyAssignment>
 */
class SurveyAssignmentFactory extends Factory
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
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
        ];
    }
}
