<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_section_id' => SurveySection::factory(),
            'type' => QuestionType::ShortText,
            'label' => fake()->sentence(6).'?',
            'help_text' => fake()->optional()->sentence(),
            'config' => [],
            'is_required' => fake()->boolean(70),
            'order' => 0,
        ];
    }

    public function type(QuestionType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => ['type' => QuestionType::SingleChoice]);
    }

    public function matrix(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Matrix,
            'config' => [
                'matrixRows' => ['Row 1', 'Row 2'],
                'matrixColumns' => ['Column 1', 'Column 2'],
            ],
        ]);
    }
}
