<?php

use App\Actions\Responses\EditResponseAnswer;
use App\Enums\QuestionType;
use App\Models\AuditLog;
use App\Models\ResponseAnswer;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function buildAnswerFor(QuestionType $type, mixed $initialValue, array $config = []): ResponseAnswer
{
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => $type,
        'label' => 'Question',
        'order' => 0,
        'is_required' => false,
        'config' => $config,
    ]);

    $response = SurveyResponse::factory()->create();

    return ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'value' => $initialValue,
    ]);
}

test('editing an answer to a valid new value updates it', function () {
    $answer = buildAnswerFor(QuestionType::Email, 'old@example.com');
    $actor = User::factory()->create();

    $updated = app(EditResponseAnswer::class)->handle($answer, 'new@example.com', $actor);

    expect($updated->value)->toBe('new@example.com')
        ->and($answer->fresh()->value)->toBe('new@example.com');
});

test('editing an answer to an invalid value for its question type is rejected', function () {
    $answer = buildAnswerFor(QuestionType::Email, 'old@example.com');
    $actor = User::factory()->create();

    expect(fn () => app(EditResponseAnswer::class)->handle($answer, 'not-an-email', $actor))
        ->toThrow(ValidationException::class);

    expect($answer->fresh()->value)->toBe('old@example.com');
});

test('editing an answer writes an audit log entry with the question, before, and after values', function () {
    $answer = buildAnswerFor(QuestionType::ShortText, 'garbage data');
    $actor = User::factory()->create();

    app(EditResponseAnswer::class)->handle($answer, 'corrected data', $actor);

    $log = AuditLog::where('action', 'response.answer_edited')->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($actor->id)
        ->and($log->properties['question_id'])->toBe($answer->survey_question_id)
        ->and($log->properties['before'])->toBe('garbage data')
        ->and($log->properties['after'])->toBe('corrected data');
});

test('editing an answer to the exact same value is a no-op with no audit entry', function () {
    $answer = buildAnswerFor(QuestionType::ShortText, 'same value');
    $actor = User::factory()->create();

    app(EditResponseAnswer::class)->handle($answer, 'same value', $actor);

    expect(AuditLog::where('action', 'response.answer_edited')->count())->toBe(0);
});

test('blanking a required questions answer is allowed', function () {
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::ShortText,
        'label' => 'Question',
        'order' => 0,
        'is_required' => true,
    ]);
    $response = SurveyResponse::factory()->create();
    $answer = ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'value' => 'garbage',
    ]);
    $actor = User::factory()->create();

    $updated = app(EditResponseAnswer::class)->handle($answer, '', $actor);

    expect($updated->value)->toBe('');
});

test('editing an answer for a single choice question still validates against its own option values', function () {
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::SingleChoice,
        'label' => 'Question',
        'order' => 0,
        'is_required' => false,
    ]);
    $question->options()->create(['label' => 'Red', 'value' => 'red', 'order' => 0]);
    $question->options()->create(['label' => 'Blue', 'value' => 'blue', 'order' => 1]);
    $question->load('options');

    $response = SurveyResponse::factory()->create();
    $answer = ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'value' => 'red',
    ]);
    $actor = User::factory()->create();

    $updated = app(EditResponseAnswer::class)->handle($answer, 'blue', $actor);
    expect($updated->value)->toBe('blue');

    expect(fn () => app(EditResponseAnswer::class)->handle($answer->fresh(), 'green', $actor))
        ->toThrow(ValidationException::class);
});
