<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Respondent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Respondent>
 */
class RespondentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
        ];
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
            'email' => null,
            'phone' => null,
        ]);
    }
}
