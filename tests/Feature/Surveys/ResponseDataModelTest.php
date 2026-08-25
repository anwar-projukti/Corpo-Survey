<?php

use App\Enums\CollectionMethod;
use App\Enums\DuplicatePolicy;
use App\Enums\ResponseStatus;
use App\Models\Organization;
use App\Models\Respondent;
use App\Models\ResponseAnswer;
use App\Models\ResponseOtp;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a respondents PII is encrypted at rest but transparent through the model', function () {
    $respondent = Respondent::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $raw = DB::table('respondents')->where('id', $respondent->id)->first();

    expect($raw->name)->not->toBe('Jane Doe')
        ->and($raw->email)->not->toBe('jane@example.com')
        ->and($respondent->fresh()->name)->toBe('Jane Doe')
        ->and($respondent->fresh()->email)->toBe('jane@example.com');
});

test('an anonymous respondent row can be created with no PII', function () {
    $respondent = Respondent::factory()->anonymous()->create();

    expect($respondent->fresh()->name)->toBeNull()
        ->and($respondent->fresh()->email)->toBeNull()
        ->and($respondent->fresh()->phone)->toBeNull();
});

test('a survey response is scoped to its organization and invisible to other organizations', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    $responseA = SurveyResponse::factory()->create(['organization_id' => $orgA->id]);
    SurveyResponse::factory()->create(['organization_id' => $orgB->id]);

    $this->actingAs($userA);

    $visible = SurveyResponse::pluck('id');

    expect($visible)->toContain($responseA->id)
        ->and($visible)->toHaveCount(1);

    $this->actingAs($userB);
    expect(SurveyResponse::pluck('id'))->not->toContain($responseA->id);
});

test('organization_id, collected_by_user_id, ip_address, and user_agent are not mass-assignable on a survey response', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $collector = User::factory()->create();

    $this->actingAs($user);

    $response = SurveyResponse::create([
        // Attempting to override the tenant via a client-supplied value —
        // BelongsToOrganization only auto-stamps when organization_id is
        // still null, so this must be rejected outright rather than
        // silently accepted.
        'organization_id' => $otherOrganization->id,
        'collected_by_user_id' => $collector->id,
        'ip_address' => '203.0.113.5',
        'user_agent' => 'malicious-client',
        'survey_id' => Survey::factory()->create()->id,
        'survey_version_id' => SurveyVersion::factory()->create()->id,
    ]);

    // None of these are fillable — a client-supplied value in the request
    // body must never be able to set who collected a response, from where,
    // or attach it to an arbitrary tenant. organization_id instead falls
    // back to the authenticated user's own organization.
    expect($response->organization_id)->toBe($organization->id)
        ->and($response->organization_id)->not->toBe($otherOrganization->id)
        ->and($response->collected_by_user_id)->toBeNull()
        ->and($response->ip_address)->toBeNull()
        ->and($response->user_agent)->toBeNull();
});

test('a response tracks completion status independently of its answers', function () {
    $partial = SurveyResponse::factory()->partial()->create();
    $complete = SurveyResponse::factory()->create();

    expect($partial->isComplete())->toBeFalse()
        ->and($partial->status)->toBe(ResponseStatus::Partial)
        ->and($complete->isComplete())->toBeTrue()
        ->and($complete->status)->toBe(ResponseStatus::Complete);
});

test('anonymizing a response strips ip address and user agent but keeps the submission', function () {
    $response = SurveyResponse::factory()->anonymized()->create();

    expect($response->fresh()->ip_address)->toBeNull()
        ->and($response->fresh()->user_agent)->toBeNull()
        ->and($response->fresh()->submitted_at)->not->toBeNull();
});

test('a field-agent-collected response records who collected it and has no respondent fingerprint', function () {
    $response = SurveyResponse::factory()->collectedByFieldAgent()->create();

    expect($response->collection_method)->toBe(CollectionMethod::FieldAgent)
        ->and($response->collected_by_user_id)->not->toBeNull()
        ->and($response->collector)->not->toBeNull()
        ->and($response->respondent_fingerprint)->toBeNull();
});

test('an answers value shape can vary freely by question type with no schema change', function () {
    $singleChoice = ResponseAnswer::factory()->create(['value' => 'yes']);
    $matrix = ResponseAnswer::factory()->create(['value' => ['Speed' => 'Great', 'Support' => 'OK']]);
    $multiSelect = ResponseAnswer::factory()->create(['value' => ['red', 'blue']]);

    expect($singleChoice->fresh()->value)->toBe('yes')
        ->and($matrix->fresh()->value)->toBe(['Speed' => 'Great', 'Support' => 'OK'])
        ->and($multiSelect->fresh()->value)->toBe(['red', 'blue']);
});

test('a survey question cannot be deleted while it has an answer attached', function () {
    $question = SurveyQuestion::factory()->create();
    ResponseAnswer::factory()->create(['survey_question_id' => $question->id]);

    expect(fn () => $question->delete())->toThrow(QueryException::class);
});

test('a survey version cannot be deleted while it has responses attached', function () {
    $version = SurveyVersion::factory()->create();
    SurveyResponse::factory()->create(['survey_version_id' => $version->id]);

    expect(fn () => $version->delete())->toThrow(QueryException::class);
});

test('a response cannot have two answers for the same question', function () {
    $response = SurveyResponse::factory()->create();
    $question = SurveyQuestion::factory()->create();

    ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
    ]);

    expect(fn () => ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
    ]))->toThrow(QueryException::class);
});

test('deleting a survey response soft deletes rather than removing it permanently', function () {
    $response = SurveyResponse::factory()->create();

    $response->delete();

    expect(SurveyResponse::find($response->id))->toBeNull()
        ->and(SurveyResponse::withTrashed()->find($response->id))->not->toBeNull();
});

test('an otp code hash never stores the plaintext code', function () {
    $otp = ResponseOtp::factory()->create();

    expect($otp->code_hash)->not->toBeNumeric()
        ->and(strlen($otp->code_hash))->toBeGreaterThan(20);
});

test('an otp reports expired and verified state correctly', function () {
    $fresh = ResponseOtp::factory()->create();
    $expired = ResponseOtp::factory()->expired()->create();
    $verified = ResponseOtp::factory()->verified()->create();

    expect($fresh->isExpired())->toBeFalse()
        ->and($fresh->isVerified())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and($verified->isVerified())->toBeTrue();
});

test('the survey version settings default to multiple responses allowed with no otp and no anonymization', function () {
    $version = SurveyVersion::factory()->create(['settings' => []]);

    $settings = $version->settingsData;

    expect($settings->duplicate_policy)->toBe(DuplicatePolicy::MultipleResponses)
        ->and($settings->require_otp)->toBeFalse()
        ->and($settings->anonymize_responses)->toBeFalse();
});

test('duplicate policy, require_otp, and anonymize_responses are independent settings', function () {
    $version = SurveyVersion::factory()->create([
        'settings' => [
            'duplicate_policy' => DuplicatePolicy::OneResponsePerUser->value,
            'require_otp' => true,
            'anonymize_responses' => true,
        ],
    ]);

    $settings = $version->settingsData;

    expect($settings->duplicate_policy)->toBe(DuplicatePolicy::OneResponsePerUser)
        ->and($settings->require_otp)->toBeTrue()
        ->and($settings->anonymize_responses)->toBeTrue();
});
