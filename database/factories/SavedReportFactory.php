<?php

namespace Database\Factories;

use App\Models\SavedReport;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReport>
 */
class SavedReportFactory extends Factory
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
            'created_by' => User::factory(),
            'name' => fake()->sentence(3),
            'config' => [
                'question_ids' => [],
                'filters' => [],
                'chart_types' => [],
                'date_from' => null,
                'date_to' => null,
            ],
        ];
    }
}
