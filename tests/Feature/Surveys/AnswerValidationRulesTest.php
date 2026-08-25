<?php

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use App\Support\AnswerValidationRules;
use Illuminate\Support\Facades\Validator;

function validateAnswer(SurveyQuestion $question, mixed $value): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(['answer' => $value], ['answer' => AnswerValidationRules::forQuestion($question)]);
}

test('a short text answer respects the builder configured max length', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::ShortText, 'config' => ['maxLength' => 5]]);

    expect(validateAnswer($question, 'short')->fails())->toBeFalse()
        ->and(validateAnswer($question, 'too long')->fails())->toBeTrue();
});

test('a number answer respects builder configured min and max', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::Number, 'config' => ['min' => 1, 'max' => 10]]);

    expect(validateAnswer($question, 5)->fails())->toBeFalse()
        ->and(validateAnswer($question, 0)->fails())->toBeTrue()
        ->and(validateAnswer($question, 11)->fails())->toBeTrue();
});

test('an email answer must be a valid email address', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::Email]);

    expect(validateAnswer($question, 'test@example.com')->fails())->toBeFalse()
        ->and(validateAnswer($question, 'not-an-email')->fails())->toBeTrue();
});

test('a single choice answer must be one of the questions own option values', function () {
    $question = SurveyQuestion::factory()->singleChoice()->create();
    $question->options()->create(['label' => 'Red', 'value' => 'red', 'order' => 0]);
    $question->options()->create(['label' => 'Blue', 'value' => 'blue', 'order' => 1]);
    $question->load('options');

    expect(validateAnswer($question, 'red')->fails())->toBeFalse()
        ->and(validateAnswer($question, 'green')->fails())->toBeTrue();
});

test('a yes no answer only accepts yes or no', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::YesNo]);

    expect(validateAnswer($question, 'yes')->fails())->toBeFalse()
        ->and(validateAnswer($question, 'maybe')->fails())->toBeTrue();
});

test('a multiple choice answer respects min and max selection counts', function () {
    $question = SurveyQuestion::factory()->create([
        'type' => QuestionType::MultipleChoice,
        'config' => ['minSelections' => 1, 'maxSelections' => 2],
    ]);

    expect(validateAnswer($question, ['a'])->fails())->toBeFalse()
        ->and(validateAnswer($question, [])->fails())->toBeTrue()
        ->and(validateAnswer($question, ['a', 'b', 'c'])->fails())->toBeTrue();
});

test('a ranking answer must contain every option exactly once', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::Ranking]);
    $question->options()->create(['label' => 'A', 'value' => 'a', 'order' => 0]);
    $question->options()->create(['label' => 'B', 'value' => 'b', 'order' => 1]);
    $question->options()->create(['label' => 'C', 'value' => 'c', 'order' => 2]);
    $question->load('options');

    expect(validateAnswer($question, ['b', 'a', 'c'])->fails())->toBeFalse()
        ->and(validateAnswer($question, ['a', 'b'])->fails())->toBeTrue()
        ->and(validateAnswer($question, ['a', 'b', 'c', 'a'])->fails())->toBeTrue()
        ->and(validateAnswer($question, ['a', 'b', 'd'])->fails())->toBeTrue();
});

test('a star rating answer must be an integer within the configured scale', function () {
    $question = SurveyQuestion::factory()->create([
        'type' => QuestionType::Star,
        'config' => ['scaleMin' => 1, 'scaleMax' => 5],
    ]);

    expect(validateAnswer($question, 3)->fails())->toBeFalse()
        ->and(validateAnswer($question, 0)->fails())->toBeTrue()
        ->and(validateAnswer($question, 6)->fails())->toBeTrue();
});

test('a matrix answer must map only known rows to known columns', function () {
    $question = SurveyQuestion::factory()->matrix()->create();

    expect(validateAnswer($question, ['Row 1' => 'Column 1'])->fails())->toBeFalse()
        ->and(validateAnswer($question, ['Row 1' => 'Not A Column'])->fails())->toBeTrue()
        ->and(validateAnswer($question, ['Not A Row' => 'Column 1'])->fails())->toBeTrue()
        ->and(validateAnswer($question, 'not-an-array')->fails())->toBeTrue();
});

test('a date answer must match the expected format', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::Date]);

    expect(validateAnswer($question, '2026-08-25')->fails())->toBeFalse()
        ->and(validateAnswer($question, '08/25/2026')->fails())->toBeTrue();
});

test('a map location answer must include numeric lat and lng', function () {
    $question = SurveyQuestion::factory()->create(['type' => QuestionType::MapLocation]);

    expect(validateAnswer($question, ['lat' => 23.8103, 'lng' => 90.4125])->fails())->toBeFalse()
        ->and(validateAnswer($question, ['lat' => 'north'])->fails())->toBeTrue()
        ->and(validateAnswer($question, 'not-an-array')->fails())->toBeTrue();
});

test('every question type produces a non empty rule list with no exceptions', function (QuestionType $type) {
    $question = SurveyQuestion::factory()->create([
        'type' => $type,
        'config' => $type === QuestionType::Matrix ? ['matrixRows' => ['R'], 'matrixColumns' => ['C']] : [],
    ]);

    expect(AnswerValidationRules::forQuestion($question))->not->toBeEmpty();
})->with(QuestionType::cases());
