<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function buildAssignedSurveyForResponses(User $fieldUser): array
{
    $survey = Survey::factory()->create(['organization_id' => $fieldUser->organization_id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::ShortText,
        'label' => 'Respondent name',
        'order' => 0,
        'is_required' => true,
    ]);

    $survey->setCurrentPublishedVersion($version);
    $survey->assignments()->create(['user_id' => $fieldUser->id]);

    return [$survey->fresh(), $version, $question];
}

test('a field user can list responses they collected', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $version] = buildAssignedSurveyForResponses($fieldUser);

    $mine = SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $fieldUser->id,
    ]);
    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => null,
    ]);

    $response = $this->actingAs($fieldUser)->getJson('/api/v1/my/responses');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.id'))->toBe($mine->id)
        ->and($response->json('data.0.survey_title'))->toBe($survey->title);
});

test('a field user can view the detail of a response they collected', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $version, $question] = buildAssignedSurveyForResponses($fieldUser);

    $mine = SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $fieldUser->id,
    ]);
    $mine->answers()->create(['survey_question_id' => $question->id, 'value' => 'Jane Doe']);

    $response = $this->actingAs($fieldUser)->getJson("/api/v1/my/responses/{$mine->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $mine->id)
        ->assertJsonCount(1, 'data.answers');
});

test('a field user cannot view the detail of a response collected by someone else', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $otherFieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $otherFieldUser->assignRole('Field User');
    [$survey, $version] = buildAssignedSurveyForResponses($fieldUser);

    $othersResponse = SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $otherFieldUser->id,
    ]);

    $this->actingAs($fieldUser)->getJson("/api/v1/my/responses/{$othersResponse->id}")->assertForbidden();
});

test('a field user only sees their own responses when filtering by survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$surveyA, $versionA] = buildAssignedSurveyForResponses($fieldUser);
    [$surveyB, $versionB] = buildAssignedSurveyForResponses($fieldUser);

    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $surveyA->id,
        'survey_version_id' => $versionA->id,
        'collected_by_user_id' => $fieldUser->id,
    ]);
    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $surveyB->id,
        'survey_version_id' => $versionB->id,
        'collected_by_user_id' => $fieldUser->id,
    ]);

    $response = $this->actingAs($fieldUser)->getJson("/api/v1/my/responses?survey_id={$surveyA->id}");

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.survey_id'))->toBe($surveyA->id);
});

test('an unauthenticated visitor cannot list or view my responses', function () {
    $this->getJson('/api/v1/my/responses')->assertUnauthorized();
});

test('submitting a response with captured location persists and returns it', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, , $question] = buildAssignedSurveyForResponses($fieldUser);

    $startedAt = now()->subMinutes(12);

    $storeResponse = $this->actingAs($fieldUser)->postJson("/api/v1/my/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Jane Doe'],
        'started_at' => $startedAt->toIso8601String(),
        'latitude' => 23.8103,
        'longitude' => 90.4125,
    ]);

    $storeResponse->assertCreated();
    $responseId = $storeResponse->json('id');

    $showResponse = $this->actingAs($fieldUser)->getJson("/api/v1/my/responses/{$responseId}");

    $showResponse->assertOk();
    expect((float) $showResponse->json('data.latitude'))->toBe(23.8103)
        ->and((float) $showResponse->json('data.longitude'))->toBe(90.4125)
        ->and($showResponse->json('data.started_at'))->not->toBeNull()
        ->and($showResponse->json('data.submitted_at'))->not->toBeNull();
});
