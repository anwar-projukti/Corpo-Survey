<?php

use App\Data\QuestionConfigData;
use App\Enums\QuestionType;

test('rules for a number question validate min/max bounds', function () {
    $rules = QuestionConfigData::rulesForType(QuestionType::Number);

    expect(validator(['min' => 1, 'max' => 10], $rules)->passes())->toBeTrue()
        ->and(validator(['min' => 10, 'max' => 1], $rules)->passes())->toBeFalse();
});

test('rules for a matrix question require matrixRows and matrixColumns', function () {
    $rules = QuestionConfigData::rulesForType(QuestionType::Matrix);

    expect(validator([], $rules)->passes())->toBeFalse()
        ->and(validator(['matrixRows' => ['A'], 'matrixColumns' => ['B']], $rules)->passes())->toBeTrue();
});

test('rules for an option-based question validate selection bounds', function () {
    $rules = QuestionConfigData::rulesForType(QuestionType::MultipleChoice);

    expect(validator(['minSelections' => 2, 'maxSelections' => 1], $rules)->passes())->toBeFalse()
        ->and(validator(['minSelections' => 1, 'maxSelections' => 3], $rules)->passes())->toBeTrue();
});

test('rules for a file upload question validate size and mime type list', function () {
    $rules = QuestionConfigData::rulesForType(QuestionType::FileUpload);

    expect(validator(['maxFileSizeKb' => 5000, 'allowedMimeTypes' => ['application/pdf']], $rules)->passes())->toBeTrue()
        ->and(validator(['maxFileSizeKb' => -1], $rules)->passes())->toBeFalse();
});

test('rules for a plain short text question do not require rating or matrix fields', function () {
    $rules = QuestionConfigData::rulesForType(QuestionType::ShortText);

    expect(validator([], $rules)->passes())->toBeTrue()
        ->and(array_key_exists('scaleMin', $rules))->toBeFalse()
        ->and(array_key_exists('matrixRows', $rules))->toBeFalse();
});

test('QuestionConfigData can be constructed from an array', function () {
    $data = QuestionConfigData::from(['min' => 1, 'max' => 5]);

    expect($data->min)->toBe(1)
        ->and($data->max)->toBe(5);
});
