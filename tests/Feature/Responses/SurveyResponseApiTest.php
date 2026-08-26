<?php

use App\Enums\QuestionType;
use App\Enums\ResponseReviewStatus;
use App\Models\Organization;
use App\Models\ResponseAnswer;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function orgAdminForResponses(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

function surveyWithPublishedVersionAndQuestion(int $organizationId): array
{
    $survey = Survey::factory()->create(['organization_id' => $organizationId]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::Number,
        'label' => 'Age',
        'order' => 0,
        'is_required' => false,
    ]);
    $survey->setCurrentPublishedVersion($version);

    return [$survey->fresh(), $version, $question];
}

test('an Org Admin can list responses for a survey', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $version] = surveyWithPublishedVersionAndQuestion($organization->id);
    SurveyResponse::factory()->count(3)->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
    ]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses");

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('the response list defaults to the currently published version', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $publishedVersion] = surveyWithPublishedVersionAndQuestion($organization->id);
    $olderVersion = SurveyVersion::factory()->archived()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $publishedVersion->id,
    ]);
    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $olderVersion->id,
    ]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses");

    $response->assertOk()->assertJsonCount(1, 'data');
});

test('a specific version can be requested explicitly', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $publishedVersion] = surveyWithPublishedVersionAndQuestion($organization->id);
    $olderVersion = SurveyVersion::factory()->archived()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $olderVersion->id,
    ]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses?version_id={$olderVersion->id}");

    $response->assertOk()->assertJsonCount(1, 'data');
});

test('responses can be filtered by an answer value', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $version, $question] = surveyWithPublishedVersionAndQuestion($organization->id);

    $matching = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $matching->id, 'survey_question_id' => $question->id, 'value' => 30]);

    $nonMatching = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $nonMatching->id, 'survey_question_id' => $question->id, 'value' => 10]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses?".http_build_query([
        'filters' => [['question_id' => $question->id, 'operator' => 'gt', 'value' => 25]],
    ]));

    $response->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);
});

test('a filter referencing a question outside the survey is rejected', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey] = surveyWithPublishedVersionAndQuestion($organization->id);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses?".http_build_query([
        'filters' => [['question_id' => 999999, 'operator' => 'equals', 'value' => 'x']],
    ]));

    $response->assertStatus(422);
});

test('an Editor without response.edit cannot list responses through an authorized survey but is blocked from editing', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    [$survey, $version] = surveyWithPublishedVersionAndQuestion($organization->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);

    $list = $this->actingAs($editor)->getJson("/api/v1/surveys/{$survey->id}/responses");
    $list->assertOk();

    $review = $this->actingAs($editor)->postJson("/api/v1/responses/{$surveyResponse->id}/review", ['review_status' => 'approved']);
    $review->assertForbidden();
});

test('an Org Admin can view a single response with its answers', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $version, $question] = surveyWithPublishedVersionAndQuestion($organization->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $surveyResponse->id, 'survey_question_id' => $question->id, 'value' => 42]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/responses/{$surveyResponse->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $surveyResponse->id)
        ->assertJsonPath('data.answers.0.value', 42);
});

test('a response from a different survey cannot be viewed through this survey', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$surveyA, $versionA] = surveyWithPublishedVersionAndQuestion($organization->id);
    [$surveyB, $versionB] = surveyWithPublishedVersionAndQuestion($organization->id);
    $responseForB = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $surveyB->id, 'survey_version_id' => $versionB->id]);

    $response = $this->actingAs($admin)->getJson("/api/v1/surveys/{$surveyA->id}/responses/{$responseForB->id}");

    $response->assertNotFound();
});

test('an Org Admin can approve a response', function () {
    [$organization, $admin] = orgAdminForResponses();
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->postJson("/api/v1/responses/{$surveyResponse->id}/review", [
        'review_status' => 'approved',
    ]);

    $response->assertOk()->assertJsonPath('data.review_status', 'approved');
    expect($surveyResponse->fresh()->review_status)->toBe(ResponseReviewStatus::Approved);
});

test('an Org Admin can add a note to a response', function () {
    [$organization, $admin] = orgAdminForResponses();
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->postJson("/api/v1/responses/{$surveyResponse->id}/notes", [
        'body' => 'Looks like a duplicate submission.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.body', 'Looks like a duplicate submission.')
        ->assertJsonPath('data.user.id', $admin->id);
});

test('an Org Admin can edit a response answer', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $version, $question] = surveyWithPublishedVersionAndQuestion($organization->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    $answer = ResponseAnswer::factory()->create(['survey_response_id' => $surveyResponse->id, 'survey_question_id' => $question->id, 'value' => 10]);

    $response = $this->actingAs($admin)->patchJson("/api/v1/response-answers/{$answer->id}", ['value' => 20]);

    $response->assertOk()->assertJsonPath('data.value', 20);
});

test('editing an answer on a response from a different organization is forbidden', function () {
    [, $admin] = orgAdminForResponses();
    $otherOrg = Organization::factory()->create();
    [$survey, $version, $question] = surveyWithPublishedVersionAndQuestion($otherOrg->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $otherOrg->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    $answer = ResponseAnswer::factory()->create(['survey_response_id' => $surveyResponse->id, 'survey_question_id' => $question->id, 'value' => 10]);

    $response = $this->actingAs($admin)->patchJson("/api/v1/response-answers/{$answer->id}", ['value' => 20]);

    $response->assertForbidden();
});

test('an Org Admin can delete a response', function () {
    [$organization, $admin] = orgAdminForResponses();
    [$survey, $version] = surveyWithPublishedVersionAndQuestion($organization->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);

    $response = $this->actingAs($admin)->deleteJson("/api/v1/surveys/{$survey->id}/responses/{$surveyResponse->id}");

    $response->assertOk();
    expect(SurveyResponse::find($surveyResponse->id))->toBeNull();
});

test('a Field User cannot delete a response', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, $version] = surveyWithPublishedVersionAndQuestion($organization->id);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);

    $response = $this->actingAs($fieldUser)->deleteJson("/api/v1/surveys/{$survey->id}/responses/{$surveyResponse->id}");

    $response->assertForbidden();
    expect(SurveyResponse::find($surveyResponse->id))->not->toBeNull();
});
