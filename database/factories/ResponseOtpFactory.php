<?php

namespace Database\Factories;

use App\Models\ResponseOtp;
use App\Models\SurveyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<ResponseOtp>
 */
class ResponseOtpFactory extends Factory
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
            'destination' => fake()->safeEmail(),
            'code_hash' => Hash::make((string) fake()->numberBetween(100000, 999999)),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
