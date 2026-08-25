<?php

use App\Models\QuestionOption;
use App\Models\SurveyQuestion;

test('an option without an explicit value defaults to its label', function () {
    $question = SurveyQuestion::factory()->singleChoice()->create();

    $option = $question->options()->create(['label' => 'Very Satisfied', 'order' => 0]);

    expect($option->value)->toBe('Very Satisfied');
});

test('an option with an explicit value keeps it rather than falling back to the label', function () {
    $question = SurveyQuestion::factory()->singleChoice()->create();

    $option = $question->options()->create(['label' => 'Very Satisfied', 'value' => '5', 'order' => 0]);

    expect($option->value)->toBe('5');
});

test('an empty-string value falls back to the label rather than staying empty', function () {
    $question = SurveyQuestion::factory()->singleChoice()->create();

    $option = QuestionOption::create([
        'survey_question_id' => $question->id,
        'label' => 'Yes',
        'value' => '',
        'order' => 0,
    ]);

    expect($option->value)->toBe('Yes');
});
