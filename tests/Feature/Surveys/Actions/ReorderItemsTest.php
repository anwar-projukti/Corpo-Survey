<?php

use App\Actions\Surveys\ReorderItems;
use App\Enums\QuestionType;
use App\Models\SurveySection;
use Illuminate\Validation\ValidationException;

test('reorders siblings to match the given id order', function () {
    $section = SurveySection::factory()->create();
    $q1 = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'A', 'order' => 0]);
    $q2 = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'B', 'order' => 1]);
    $q3 = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'C', 'order' => 2]);

    (new ReorderItems)->handle($section->questions(), [$q3->id, $q1->id, $q2->id]);

    expect($section->questions()->orderBy('order')->pluck('id')->all())->toBe([$q3->id, $q1->id, $q2->id]);
});

test('rejects an order list missing an existing item', function () {
    $section = SurveySection::factory()->create();
    $q1 = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'A', 'order' => 0]);
    $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'B', 'order' => 1]);

    (new ReorderItems)->handle($section->questions(), [$q1->id]);
})->throws(ValidationException::class);

test('rejects an order list containing an id from a different parent', function () {
    $sectionA = SurveySection::factory()->create();
    $sectionB = SurveySection::factory()->create();
    $q1 = $sectionA->questions()->create(['type' => QuestionType::ShortText, 'label' => 'A', 'order' => 0]);
    $foreign = $sectionB->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Foreign', 'order' => 0]);

    (new ReorderItems)->handle($sectionA->questions(), [$q1->id, $foreign->id]);
})->throws(ValidationException::class);
