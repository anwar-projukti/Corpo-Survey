<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\ResponseAnswer;
use App\Models\SavedReport;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function orgAdminForSavedReports(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

function surveyWithQuestionForSavedReports(int $organizationId): array
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

test('an Org Admin can create a saved report for their survey', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey, , $question] = surveyWithQuestionForSavedReports($organization->id);

    $result = $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/saved-reports", [
        'name' => 'Satisfaction breakdown',
        'config' => [
            'question_ids' => [$question->id],
            'filters' => [],
            'chart_types' => [$question->id => 'doughnut'],
            'date_from' => null,
            'date_to' => null,
        ],
    ]);

    $result->assertCreated()
        ->assertJsonPath('data.name', 'Satisfaction breakdown')
        ->assertJsonPath('data.config.question_ids.0', $question->id);

    $this->assertDatabaseHas('saved_reports', ['survey_id' => $survey->id, 'name' => 'Satisfaction breakdown']);
});

test('a report cannot reference a question outside the surveys current version', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey] = surveyWithQuestionForSavedReports($organization->id);

    $result = $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/saved-reports", [
        'name' => 'Bad report',
        'config' => ['question_ids' => [999999]],
    ]);

    $result->assertUnprocessable()->assertJsonValidationErrors(['config.question_ids']);
});

test('a report with an invalid filter operator is rejected', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey, , $question] = surveyWithQuestionForSavedReports($organization->id);

    $result = $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/saved-reports", [
        'name' => 'Bad filter',
        'config' => [
            'question_ids' => [$question->id],
            'filters' => [['question_id' => $question->id, 'operator' => 'bogus']],
        ],
    ]);

    $result->assertUnprocessable();
});

test('a Field User cannot create a saved report', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey, , $question] = surveyWithQuestionForSavedReports($organization->id);

    $result = $this->actingAs($fieldUser)->postJson("/api/v1/surveys/{$survey->id}/saved-reports", [
        'name' => 'Nope',
        'config' => ['question_ids' => [$question->id]],
    ]);

    $result->assertForbidden();
});

test('an Editor cannot create a saved report but can view existing ones', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    [$survey, , $question] = surveyWithQuestionForSavedReports($organization->id);
    SavedReport::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    $this->actingAs($editor)->postJson("/api/v1/surveys/{$survey->id}/saved-reports", [
        'name' => 'Nope',
        'config' => ['question_ids' => [$question->id]],
    ])->assertForbidden();

    $this->actingAs($editor)->getJson("/api/v1/surveys/{$survey->id}/saved-reports")->assertOk();
});

test('an Org Admin can list saved reports for their survey', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey] = surveyWithQuestionForSavedReports($organization->id);
    SavedReport::factory()->count(2)->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    $result = $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/saved-reports");

    $result->assertOk()->assertJsonCount(2, 'data');
});

test('an Org Admin can update a saved report', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey, , $question] = surveyWithQuestionForSavedReports($organization->id);
    $report = SavedReport::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'config' => ['question_ids' => [$question->id]],
    ]);

    $result = $this->actingAs($admin)->patchJson("/api/v1/saved-reports/{$report->id}", [
        'name' => 'Renamed',
        'config' => ['question_ids' => [$question->id], 'filters' => []],
    ]);

    $result->assertOk()->assertJsonPath('data.name', 'Renamed');
});

test('an Org Admin can delete a saved report', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey] = surveyWithQuestionForSavedReports($organization->id);
    $report = SavedReport::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    $result = $this->actingAs($admin)->deleteJson("/api/v1/saved-reports/{$report->id}");

    $result->assertOk();
    $this->assertDatabaseMissing('saved_reports', ['id' => $report->id]);
});

test('a user cannot manage saved reports belonging to a different organization', function () {
    // Survey's and SavedReport's BelongsToOrganization global scope filters
    // the row out of the query entirely before route-model binding
    // resolves it, so this 404s rather than 403s — the same behavior
    // SurveyPolicyTest documents for every other survey-scoped endpoint.
    [, $admin] = orgAdminForSavedReports();
    $otherOrg = Organization::factory()->create();
    [$survey] = surveyWithQuestionForSavedReports($otherOrg->id);
    $report = SavedReport::factory()->create(['organization_id' => $otherOrg->id, 'survey_id' => $survey->id]);

    $this->actingAs($admin)->getJson("/api/v1/surveys/{$survey->id}/saved-reports")->assertNotFound();
    $this->actingAs($admin)->patchJson("/api/v1/saved-reports/{$report->id}", ['name' => 'x', 'config' => []])->assertNotFound();
    $this->actingAs($admin)->deleteJson("/api/v1/saved-reports/{$report->id}")->assertNotFound();
});

test('running a saved report returns a filtered distribution for its selected questions', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey, $version, $question] = surveyWithQuestionForSavedReports($organization->id);

    $responseA = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $responseA->id, 'survey_question_id' => $question->id, 'value' => 'Satisfied']);

    $responseB = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id]);
    ResponseAnswer::factory()->create(['survey_response_id' => $responseB->id, 'survey_question_id' => $question->id, 'value' => 'Dissatisfied']);

    $report = SavedReport::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'config' => [
            'question_ids' => [$question->id],
            'filters' => [['question_id' => $question->id, 'operator' => 'equals', 'value' => 'Satisfied']],
        ],
    ]);

    $result = $this->actingAs($admin)->getJson("/api/v1/saved-reports/{$report->id}/run");

    $result->assertOk()
        ->assertJsonPath('data.response_count', 1)
        ->assertJsonPath('data.questions.0.distribution.Satisfied', 1);
});

test('running a saved report respects its date range', function () {
    [$organization, $admin] = orgAdminForSavedReports();
    [$survey, $version, $question] = surveyWithQuestionForSavedReports($organization->id);

    SurveyResponse::factory()->create([
        'organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id,
        'submitted_at' => now()->subMonths(2),
    ]);
    SurveyResponse::factory()->create([
        'organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id,
        'submitted_at' => now(),
    ]);

    $report = SavedReport::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'config' => [
            'question_ids' => [$question->id],
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ],
    ]);

    $result = $this->actingAs($admin)->getJson("/api/v1/saved-reports/{$report->id}/run");

    $result->assertOk()->assertJsonPath('data.response_count', 1);
});
