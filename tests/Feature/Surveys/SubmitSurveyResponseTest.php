<?php

use App\Actions\Surveys\SubmitSurveyResponse;
use App\Enums\CollectionMethod;
use App\Enums\QuestionType;
use App\Enums\ResponseStatus;
use App\Enums\RuleAction;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyVersion;
use Illuminate\Validation\ValidationException;

function buildSubmittableVersion(): array
{
    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);

    $name = $section->questions()->create([
        'type' => QuestionType::ShortText,
        'label' => 'Your name',
        'order' => 0,
        'is_required' => true,
    ]);

    $email = $section->questions()->create([
        'type' => QuestionType::Email,
        'label' => 'Your email',
        'order' => 1,
        'is_required' => false,
    ]);

    return [$version->fresh(['sections.questions', 'rules', 'survey']), $name, $email];
}

test('a valid submission with all required answers is persisted as complete', function () {
    [$version, $name, $email] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe', $email->id => 'jane@example.com'],
        fingerprint: 'fp-1',
    );

    expect($response->status)->toBe(ResponseStatus::Complete)
        ->and($response->answers)->toHaveCount(2)
        ->and($response->answers->firstWhere('survey_question_id', $name->id)->value)->toBe('Jane Doe');
});

test('a submission missing a required answer is rejected', function () {
    [$version, $name] = buildSubmittableVersion();

    expect(fn () => (new SubmitSurveyResponse)->handle($version, [], fingerprint: 'fp-1'))
        ->toThrow(ValidationException::class);
});

test('a submission with an invalid answer for its question type is rejected', function () {
    [$version, $name, $email] = buildSubmittableVersion();

    expect(fn () => (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe', $email->id => 'not-an-email'],
        fingerprint: 'fp-1',
    ))->toThrow(ValidationException::class);
});

test('an answer for a question outside this survey version is rejected', function () {
    [$version, $name] = buildSubmittableVersion();

    expect(fn () => (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe', 999999 => 'stray answer'],
        fingerprint: 'fp-1',
    ))->toThrow(ValidationException::class);
});

test('an answer to a question hidden by conditional logic is silently dropped rather than rejected', function () {
    [$version, $name, $email] = buildSubmittableVersion();

    $version->rules()->create([
        'name' => 'Hide email always',
        'conditions' => ['question_id' => $name->id, 'operator' => 'is_answered'],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $email->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version = $version->fresh(['sections.questions', 'rules', 'survey']);

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe', $email->id => 'not-an-email-but-hidden'],
        fingerprint: 'fp-1',
    );

    expect($response->answers)->toHaveCount(1)
        ->and($response->answers->pluck('survey_question_id')->all())->toBe([$name->id]);
});

test('a submission that triggers end_survey is stored as partial', function () {
    [$version, $name, $email] = buildSubmittableVersion();

    $version->rules()->create([
        'name' => 'End survey immediately',
        'conditions' => ['question_id' => $name->id, 'operator' => 'is_answered'],
        'action' => RuleAction::EndSurvey,
        'target' => null,
        'order' => 0,
        'is_active' => true,
    ]);
    $version = $version->fresh(['sections.questions', 'rules', 'survey']);

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe'],
        fingerprint: 'fp-1',
    );

    expect($response->status)->toBe(ResponseStatus::Partial);
});

test('a submission stamps the surveys own organization_id regardless of any ambient auth state', function () {
    [$version] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$version->sections->first()->questions->first()->id => 'Jane Doe'],
        fingerprint: 'fp-1',
    );

    expect($response->organization_id)->toBe($version->survey->organization_id);
});

test('a submission is marked self serve collection with the given fingerprint', function () {
    [$version, $name] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle($version, [$name->id => 'Jane Doe'], fingerprint: 'fp-xyz');

    expect($response->collection_method)->toBe(CollectionMethod::SelfServe)
        ->and($response->respondent_fingerprint)->toBe('fp-xyz')
        ->and($response->collected_by_user_id)->toBeNull();
});

test('anonymize strips ip and user agent from the stored response', function () {
    [$version, $name] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe'],
        fingerprint: 'fp-1',
        context: ['ip_address' => '203.0.113.5', 'user_agent' => 'TestAgent/1.0'],
        anonymize: true,
    );

    expect($response->ip_address)->toBeNull()
        ->and($response->user_agent)->toBeNull();
});

test('without anonymize the ip and user agent from context are stored', function () {
    [$version, $name] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe'],
        fingerprint: 'fp-1',
        context: ['ip_address' => '203.0.113.5', 'user_agent' => 'TestAgent/1.0'],
        anonymize: false,
    );

    expect($response->ip_address)->toBe('203.0.113.5')
        ->and($response->user_agent)->toBe('TestAgent/1.0');
});

test('an optional question left blank does not block submission', function () {
    [$version, $name, $email] = buildSubmittableVersion();

    $response = (new SubmitSurveyResponse)->handle(
        $version,
        [$name->id => 'Jane Doe', $email->id => ''],
        fingerprint: 'fp-1',
    );

    expect($response->answers)->toHaveCount(1);
});
