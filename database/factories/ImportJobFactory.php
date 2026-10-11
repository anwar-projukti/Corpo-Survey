<?php

namespace Database\Factories;

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportJob>
 */
class ImportJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requested_by' => User::factory(),
            'original_filename' => 'respondents.xlsx',
            'file_path' => 'imports/test/'.fake()->uuid().'.xlsx',
            'status' => 'uploaded',
            'detected_columns' => ['Name', 'Email', 'Phone'],
        ];
    }
}
