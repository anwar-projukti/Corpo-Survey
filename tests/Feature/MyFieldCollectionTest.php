<?php

use App\Enums\CollectionMethod;
use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function buildAssignedSurvey(User $fieldUser): array
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

    return [$survey->fresh(), $question];
}

test('a field user only sees surveys assigned to them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    [$assignedSurvey] = buildAssignedSurvey($fieldUser);
    $unassignedSurvey = Survey::factory()->create(['organization_id' => $organization->id]);
    $unassignedVersion = SurveyVersion::factory()->published()->create(['survey_id' => $unassignedSurvey->id]);
    $unassignedSurvey->setCurrentPublishedVersion($unassignedVersion);

    $response = $this->actingAs($fieldUser)->getJson('/api/v1/my/surveys');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.id'))->toBe($assignedSurvey->id);
});

test('a field user can view a single assigned surveys structure', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $question] = buildAssignedSurvey($fieldUser);

    $response = $this->actingAs($fieldUser)->getJson("/api/v1/my/surveys/{$survey->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $survey->id)
        ->assertJsonPath('data.version.sections.0.questions.0.id', $question->id);
});

test('a field user cannot view a survey that is not assigned to them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $survey->setCurrentPublishedVersion($version);

    $response = $this->actingAs($fieldUser)->getJson("/api/v1/my/surveys/{$survey->id}");

    $response->assertForbidden();
});

test('a field user can submit a response and it is tagged with their identity', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $question] = buildAssignedSurvey($fieldUser);

    $response = $this->actingAs($fieldUser)->postJson("/api/v1/my/surveys/{$survey->id}/responses", [
        'answers' => [(string) $question->id => 'Jane Doe'],
    ]);

    $response->assertCreated();

    $stored = SurveyResponse::first();
    expect($stored->collection_method)->toBe(CollectionMethod::FieldAgent)
        ->and($stored->collected_by_user_id)->toBe($fieldUser->id);
});

test('a field user cannot submit a response for a survey not assigned to them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $survey->setCurrentPublishedVersion($version);

    $response = $this->actingAs($fieldUser)->postJson("/api/v1/my/surveys/{$survey->id}/responses", [
        'answers' => [],
    ]);

    $response->assertForbidden();
});

test('resubmitting the same client_submission_id does not create a duplicate response', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $question] = buildAssignedSurvey($fieldUser);

    $payload = [
        'answers' => [(string) $question->id => 'Jane Doe'],
        'client_submission_id' => 'offline-queue-item-1',
    ];

    $first = $this->actingAs($fieldUser)->postJson("/api/v1/my/surveys/{$survey->id}/responses", $payload);
    $second = $this->actingAs($fieldUser)->postJson("/api/v1/my/surveys/{$survey->id}/responses", $payload);

    $first->assertCreated();
    $second->assertCreated();
    expect($first->json('id'))->toBe($second->json('id'))
        ->and(SurveyResponse::count())->toBe(1);
});

test('the same client_submission_id from two different field users creates two responses', function () {
    $organization = Organization::factory()->create();
    $fieldUserA = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUserA->assignRole('Field User');
    $fieldUserB = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUserB->assignRole('Field User');

    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Name', 'order' => 0, 'is_required' => false]);
    $survey->setCurrentPublishedVersion($version);
    $survey->assignments()->create(['user_id' => $fieldUserA->id]);
    $survey->assignments()->create(['user_id' => $fieldUserB->id]);

    $payload = ['answers' => [(string) $question->id => 'X'], 'client_submission_id' => 'same-id'];

    $this->actingAs($fieldUserA)->postJson("/api/v1/my/surveys/{$survey->id}/responses", $payload)->assertCreated();
    $this->actingAs($fieldUserB)->postJson("/api/v1/my/surveys/{$survey->id}/responses", $payload)->assertCreated();

    expect(SurveyResponse::count())->toBe(2);
});

test('my stats reports totals scoped to the authenticated field user only', function () {
    $organization = Organization::factory()->create();
    $fieldUserA = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUserA->assignRole('Field User');
    $fieldUserB = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUserB->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->create(['survey_id' => $survey->id]);

    SurveyResponse::factory()->count(3)->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $fieldUserA->id,
        'collection_method' => CollectionMethod::FieldAgent,
        'submitted_at' => now(),
    ]);
    SurveyResponse::factory()->count(5)->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $fieldUserB->id,
        'collection_method' => CollectionMethod::FieldAgent,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($fieldUserA)->getJson('/api/v1/my/stats');

    $response->assertOk()
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.today', 3);
});
