<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\ResponseAnswer;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function orgAdminForAnalytics(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

function surveyWithQuestionForAnalytics(int $organizationId): array
{
    $survey = Survey::factory()->create(['organization_id' => $organizationId]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::SingleChoice,
        'label' => 'Satisfaction',
        'order' => 0,
        'is_required' => false,
    ]);
    $survey->setCurrentPublishedVersion($version);

    return [$survey->fresh(), $version, $question];
}

test('an Org Admin can view analytics for their survey', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey, $version, $question] = surveyWithQuestionForAnalytics($organization->id);

    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $response->id, 'survey_question_id' => $question->id, 'value' => 'Satisfied']);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics");

    $result->assertOk()
        ->assertJsonPath('data.overview.total', 1)
        ->assertJsonPath('data.questions.0.distribution.Satisfied', 1);
});

test('a Field User cannot view analytics', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey] = surveyWithQuestionForAnalytics($organization->id);

    $result = $this->actingAs($fieldUser)->getJson("/api/v1/surveys/{$survey->id}/analytics");

    $result->assertForbidden();
});

test('analytics defaults to the currently published version', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey, $publishedVersion] = surveyWithQuestionForAnalytics($organization->id);
    $olderVersion = SurveyVersion::factory()->archived()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $publishedVersion->id]);
    SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $olderVersion->id]);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics");

    $result->assertOk()->assertJsonPath('data.overview.total', 1);
});

test('analytics accepts an explicit version_id', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey, , $question] = surveyWithQuestionForAnalytics($organization->id);
    $olderVersion = SurveyVersion::factory()->archived()->create(['survey_id' => $survey->id, 'version_number' => 2]);
    SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $olderVersion->id]);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics?version_id={$olderVersion->id}");

    $result->assertOk()
        ->assertJsonPath('data.version_id', $olderVersion->id)
        ->assertJsonPath('data.overview.total', 1);
});

test('analytics accepts a trend granularity parameter', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey, $version] = surveyWithQuestionForAnalytics($organization->id);
    SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id, 'submitted_at' => now()]);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics?granularity=month");

    $result->assertOk()->assertJsonCount(1, 'data.trend');
});

test('an invalid granularity falls back to day rather than erroring', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey] = surveyWithQuestionForAnalytics($organization->id);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics?granularity=bogus");

    $result->assertOk();
});

test('analytics for a survey with no responses returns zeroed metrics without error', function () {
    [$organization, $admin] = orgAdminForAnalytics();
    [$survey] = surveyWithQuestionForAnalytics($organization->id);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/analytics");

    $result->assertOk()
        ->assertJsonPath('data.overview.total', 0)
        ->assertJsonPath('data.overview.completion_rate', 0)
        ->assertJsonPath('data.drop_off', []);
});
