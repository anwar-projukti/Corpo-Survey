<?php

use App\Enums\QuestionType;
use App\Models\ResponseAnswer;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Support\ResponseFilterEngine;
use Illuminate\Validation\ValidationException;

function buildResponseWithAnswer(int $questionId, mixed $value): SurveyResponse
{
    $response = SurveyResponse::factory()->create();
    ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $questionId,
        'value' => $value,
    ]);

    return $response;
}

function makeQuestion(QuestionType $type = QuestionType::ShortText)
{
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);

    return $section->questions()->create([
        'type' => $type,
        'label' => 'Question',
        'order' => 0,
        'is_required' => false,
    ]);
}

test('equals filters responses whose answer matches exactly', function () {
    $question = makeQuestion();
    $matching = buildResponseWithAnswer($question->id, 'Dhaka');
    buildResponseWithAnswer($question->id, 'Chittagong');

    $results = ResponseFilterEngine::apply(
        SurveyResponse::query(),
        [['question_id' => $question->id, 'operator' => 'equals', 'value' => 'Dhaka']],
    )->get();

    expect($results->pluck('id')->all())->toBe([$matching->id]);
});

test('not_equals excludes responses whose answer matches', function () {
    $question = makeQuestion();
    buildResponseWithAnswer($question->id, 'Dhaka');
    $other = buildResponseWithAnswer($question->id, 'Chittagong');

    $results = ResponseFilterEngine::apply(
        SurveyResponse::query(),
        [['question_id' => $question->id, 'operator' => 'not_equals', 'value' => 'Dhaka']],
    )->get();

    expect($results->pluck('id')->all())->toBe([$other->id]);
});

test('gt gte lt lte compare numeric answers correctly', function () {
    $question = makeQuestion(QuestionType::Number);
    $young = buildResponseWithAnswer($question->id, 18);
    $old = buildResponseWithAnswer($question->id, 45);

    $gt25 = ResponseFilterEngine::apply(SurveyResponse::query(), [
        ['question_id' => $question->id, 'operator' => 'gt', 'value' => 25],
    ])->get();
    expect($gt25->pluck('id')->all())->toBe([$old->id]);

    $lt25 = ResponseFilterEngine::apply(SurveyResponse::query(), [
        ['question_id' => $question->id, 'operator' => 'lt', 'value' => 25],
    ])->get();
    expect($lt25->pluck('id')->all())->toBe([$young->id]);

    $gte45 = ResponseFilterEngine::apply(SurveyResponse::query(), [
        ['question_id' => $question->id, 'operator' => 'gte', 'value' => 45],
    ])->get();
    expect($gte45->pluck('id')->all())->toBe([$old->id]);

    $lte18 = ResponseFilterEngine::apply(SurveyResponse::query(), [
        ['question_id' => $question->id, 'operator' => 'lte', 'value' => 18],
    ])->get();
    expect($lte18->pluck('id')->all())->toBe([$young->id]);
});

test('contains matches a value inside a multi select array answer', function () {
    $question = makeQuestion(QuestionType::MultipleChoice);
    $matching = buildResponseWithAnswer($question->id, ['red', 'blue']);
    buildResponseWithAnswer($question->id, ['green']);

    $results = ResponseFilterEngine::apply(
        SurveyResponse::query(),
        [['question_id' => $question->id, 'operator' => 'contains', 'value' => 'blue']],
    )->get();

    expect($results->pluck('id')->all())->toBe([$matching->id]);
});

test('is_answered only matches responses with a non blank answer to that question', function () {
    $question = makeQuestion();
    $answered = buildResponseWithAnswer($question->id, 'something');
    $blank = buildResponseWithAnswer($question->id, '');
    $unanswered = SurveyResponse::factory()->create();

    $results = ResponseFilterEngine::apply(
        SurveyResponse::query(),
        [['question_id' => $question->id, 'operator' => 'is_answered']],
    )->get();

    expect($results->pluck('id')->all())->toBe([$answered->id])
        ->and($results->pluck('id')->all())->not->toContain($blank->id)
        ->and($results->pluck('id')->all())->not->toContain($unanswered->id);
});

test('is_not_answered matches responses with no answer or a blank answer to that question', function () {
    $question = makeQuestion();
    buildResponseWithAnswer($question->id, 'something');
    $blank = buildResponseWithAnswer($question->id, '');
    $unanswered = SurveyResponse::factory()->create();

    $results = ResponseFilterEngine::apply(
        SurveyResponse::query(),
        [['question_id' => $question->id, 'operator' => 'is_not_answered']],
    )->get();

    expect($results->pluck('id')->all())->toContain($blank->id)
        ->and($results->pluck('id')->all())->toContain($unanswered->id);
});

test('multiple filters are ANDed together', function () {
    $ageQuestion = makeQuestion(QuestionType::Number);
    $cityQuestion = makeQuestion();

    $response = SurveyResponse::factory()->create();
    ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $ageQuestion->id, 'value' => 30]);
    ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $cityQuestion->id, 'value' => 'Dhaka']);

    $otherResponse = SurveyResponse::factory()->create();
    ResponseAnswer::factory()->create(['survey_response_id' => $otherResponse->id, 'survey_question_id' => $ageQuestion->id, 'value' => 30]);
    ResponseAnswer::factory()->create(['survey_response_id' => $otherResponse->id, 'survey_question_id' => $cityQuestion->id, 'value' => 'Chittagong']);

    $results = ResponseFilterEngine::apply(SurveyResponse::query(), [
        ['question_id' => $ageQuestion->id, 'operator' => 'gt', 'value' => 25],
        ['question_id' => $cityQuestion->id, 'operator' => 'equals', 'value' => 'Dhaka'],
    ])->get();

    expect($results->pluck('id')->all())->toBe([$response->id]);
});

test('validate rejects a filter referencing a question outside the given valid set', function () {
    expect(fn () => ResponseFilterEngine::validate(
        [['question_id' => 999, 'operator' => 'equals', 'value' => 'x']],
        validQuestionIds: [1, 2, 3],
    ))->toThrow(ValidationException::class);
});

test('validate rejects an unrecognized operator', function () {
    expect(fn () => ResponseFilterEngine::validate(
        [['question_id' => 1, 'operator' => 'bogus', 'value' => 'x']],
        validQuestionIds: [1],
    ))->toThrow(ValidationException::class);
});

test('validate rejects a value requiring operator with no value', function () {
    expect(fn () => ResponseFilterEngine::validate(
        [['question_id' => 1, 'operator' => 'equals']],
        validQuestionIds: [1],
    ))->toThrow(ValidationException::class);
});

test('validate accepts is_answered and is_not_answered with no value', function () {
    ResponseFilterEngine::validate(
        [['question_id' => 1, 'operator' => 'is_answered']],
        validQuestionIds: [1],
    );

    ResponseFilterEngine::validate(
        [['question_id' => 1, 'operator' => 'is_not_answered']],
        validQuestionIds: [1],
    );
})->throwsNoExceptions();
