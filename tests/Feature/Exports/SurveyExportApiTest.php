<?php

use App\Enums\QuestionType;
use App\Jobs\GenerateOrganizationExportJob;
use App\Jobs\GenerateSurveyExportJob;
use App\Models\ExportJob;
use App\Models\Organization;
use App\Models\ResponseAnswer;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function orgAdminForExports(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

function surveyWithQuestionForExports(int $organizationId): array
{
    $survey = Survey::factory()->create(['organization_id' => $organizationId]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $question = $section->questions()->create([
        'type' => QuestionType::ShortText,
        'label' => 'Comment',
        'order' => 0,
        'is_required' => false,
    ]);
    $survey->setCurrentPublishedVersion($version);

    return [$survey->fresh(), $version, $question];
}

test('an Org Admin can request a survey export and it dispatches a queued job', function () {
    Queue::fake();
    [$organization, $admin] = orgAdminForExports();
    [$survey] = surveyWithQuestionForExports($organization->id);

    $result = $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'xlsx',
        'scope' => 'all',
    ]);

    $result->assertStatus(202)->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('export_jobs', ['survey_id' => $survey->id, 'type' => 'survey', 'format' => 'xlsx']);
    Queue::assertPushed(GenerateSurveyExportJob::class);
});

test('a Field User cannot request a survey export', function () {
    [$organization] = orgAdminForExports();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    [$survey] = surveyWithQuestionForExports($organization->id);

    $this->actingAs($fieldUser)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'xlsx',
        'scope' => 'all',
    ])->assertForbidden();
});

test('a filtered export requires filters and a selected export requires response ids', function () {
    [$organization, $admin] = orgAdminForExports();
    [$survey] = surveyWithQuestionForExports($organization->id);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'xlsx',
        'scope' => 'filtered',
    ])->assertUnprocessable()->assertJsonValidationErrors(['filters']);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'xlsx',
        'scope' => 'selected',
    ])->assertUnprocessable()->assertJsonValidationErrors(['response_ids']);
});

test('an invalid export format is rejected', function () {
    [$organization, $admin] = orgAdminForExports();
    [$survey] = surveyWithQuestionForExports($organization->id);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'pdf',
        'scope' => 'all',
    ])->assertUnprocessable()->assertJsonValidationErrors(['format']);
});

test('the queued export job writes a real file and completes the export job record', function () {
    Storage::fake('local');
    [$organization, $admin] = orgAdminForExports();
    [$survey, $version, $question] = surveyWithQuestionForExports($organization->id);

    $response = SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
    ]);
    ResponseAnswer::factory()->create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $question->id,
        'value' => 'Great survey',
    ]);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/export", [
        'format' => 'xlsx',
        'scope' => 'all',
    ])->assertStatus(202);

    $exportJob = ExportJob::first();
    expect($exportJob->status)->toBe('completed');
    expect($exportJob->row_count)->toBe(1);
    Storage::disk('local')->assertExists($exportJob->file_path);
});

test('an Org Admin can request an organization wide export', function () {
    Queue::fake();
    [$organization, $admin] = orgAdminForExports();

    $result = $this->actingAs($admin)->postJson("/api/v1/organizations/{$organization->id}/export", [
        'format' => 'xlsx',
    ]);

    $result->assertStatus(202);
    $this->assertDatabaseHas('export_jobs', ['organization_id' => $organization->id, 'type' => 'organization']);
    Queue::assertPushed(GenerateOrganizationExportJob::class);
});

test('a user cannot request an export for another organization', function () {
    [, $admin] = orgAdminForExports();
    $otherOrg = Organization::factory()->create();

    $this->actingAs($admin)->postJson("/api/v1/organizations/{$otherOrg->id}/export", [
        'format' => 'xlsx',
    ])->assertForbidden();
});

test('the export history endpoint lists completed and pending export jobs', function () {
    [$organization, $admin] = orgAdminForExports();
    ExportJob::factory()->count(3)->create(['organization_id' => $organization->id, 'requested_by' => $admin->id]);

    $result = $this->actingAs($admin)->getJson('/api/v1/exports');

    $result->assertOk()->assertJsonCount(3, 'data');
});

test('the signed download link rejects a tampered signature', function () {
    [$organization, $admin] = orgAdminForExports();
    $exportJob = ExportJob::factory()->create([
        'organization_id' => $organization->id,
        'status' => 'completed',
        'file_path' => 'exports/test.xlsx',
    ]);

    $this->get("/exports/{$exportJob->id}/download?signature=bogus")->assertForbidden();
});
