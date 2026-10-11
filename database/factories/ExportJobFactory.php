<?php

namespace Database\Factories;

use App\Models\ExportJob;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportJob>
 */
class ExportJobFactory extends Factory
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
            'requested_by' => User::factory(),
            'type' => 'survey',
            'format' => 'xlsx',
            'status' => 'pending',
            'options' => ['scope' => 'all'],
        ];
    }
}
