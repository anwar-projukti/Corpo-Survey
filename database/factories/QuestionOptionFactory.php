<?php

namespace Database\Factories;

use App\Models\QuestionOption;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->words(2, true);

        return [
            'survey_question_id' => SurveyQuestion::factory(),
            'label' => $label,
            'value' => $label,
            'order' => 0,
        ];
    }
}
