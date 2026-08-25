<?php

use App\Enums\QuestionType;

test('usesOptions is true for choice-based question types', function () {
    expect(QuestionType::SingleChoice->usesOptions())->toBeTrue()
        ->and(QuestionType::MultipleChoice->usesOptions())->toBeTrue()
        ->and(QuestionType::Dropdown->usesOptions())->toBeTrue()
        ->and(QuestionType::Ranking->usesOptions())->toBeTrue()
        ->and(QuestionType::ImageChoice->usesOptions())->toBeTrue();
});

test('usesOptions is false for non-choice question types', function () {
    expect(QuestionType::ShortText->usesOptions())->toBeFalse()
        ->and(QuestionType::Matrix->usesOptions())->toBeFalse()
        ->and(QuestionType::Nps->usesOptions())->toBeFalse();
});

test('isFileType is true only for upload/signature question types', function () {
    expect(QuestionType::FileUpload->isFileType())->toBeTrue()
        ->and(QuestionType::ImageUpload->isFileType())->toBeTrue()
        ->and(QuestionType::Signature->isFileType())->toBeTrue()
        ->and(QuestionType::ShortText->isFileType())->toBeFalse()
        ->and(QuestionType::SingleChoice->isFileType())->toBeFalse();
});

test('every question type from the spec is represented', function () {
    $expected = [
        'short_text', 'long_text', 'number', 'email', 'phone', 'url',
        'single_choice', 'multiple_choice', 'dropdown', 'searchable_dropdown', 'yes_no',
        'star', 'numeric_rating', 'emoji', 'likert', 'nps', 'satisfaction',
        'matrix', 'ranking', 'slider', 'range', 'image_choice', 'date', 'time', 'date_time',
        'file_upload', 'image_upload', 'signature',
        'country', 'division', 'district', 'city', 'address', 'map_location',
    ];

    $actual = array_map(fn (QuestionType $type) => $type->value, QuestionType::cases());

    expect($actual)->toEqualCanonicalizing($expected);
});
