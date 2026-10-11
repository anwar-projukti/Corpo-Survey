<?php

use App\Enums\QuestionType;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

function orgAdminForAuditLog(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

test('audit log entries cannot be updated', function () {
    $log = AuditLog::create(['action' => 'test.action', 'created_at' => now()]);

    expect(fn () => $log->update(['action' => 'tampered']))->toThrow(LogicException::class);
});

test('audit log entries cannot be deleted', function () {
    $log = AuditLog::create(['action' => 'test.action', 'created_at' => now()]);

    expect(fn () => $log->delete())->toThrow(LogicException::class);
});

test('creating a survey is audited', function () {
    [, $admin] = orgAdminForAuditLog();

    $this->actingAs($admin)->postJson('/api/v1/surveys', ['title' => 'Audited Survey'])->assertCreated();

    $this->assertDatabaseHas('audit_logs', ['action' => 'survey.created', 'actor_id' => $admin->id]);
});

test('updating a survey is audited with before and after values', function () {
    [$organization, $admin] = orgAdminForAuditLog();
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'title' => 'Original']);

    $this->actingAs($admin)->putJson("/api/v1/surveys/{$survey->id}", ['title' => 'Renamed'])->assertOk();

    $log = AuditLog::where('action', 'survey.updated')->first();
    expect($log)->not->toBeNull();
    expect($log->properties['before']['title'])->toBe('Original');
    expect($log->properties['after']['title'])->toBe('Renamed');
});

test('deleting a survey is audited', function () {
    [$organization, $admin] = orgAdminForAuditLog();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($admin)->deleteJson("/api/v1/surveys/{$survey->id}")->assertOk();

    $this->assertDatabaseHas('audit_logs', ['action' => 'survey.deleted', 'actor_id' => $admin->id]);
});

test('publishing a survey is audited', function () {
    [$organization, $admin] = orgAdminForAuditLog();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $draft = SurveyVersion::factory()->create(['survey_id' => $survey->id]);
    $draft->sections()->create(['title' => 'S', 'order' => 0])->questions()->create([
        'type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0, 'is_required' => false,
    ]);
    $survey->setCurrentDraftVersion($draft);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/publish")->assertOk();

    $this->assertDatabaseHas('audit_logs', ['action' => 'survey.published', 'actor_id' => $admin->id]);
});

test('closing a survey is audited', function () {
    [$organization, $admin] = orgAdminForAuditLog();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($admin)->postJson("/api/v1/surveys/{$survey->id}/close")->assertOk();

    $this->assertDatabaseHas('audit_logs', ['action' => 'survey.closed', 'actor_id' => $admin->id]);
});

test('deleting a response is audited', function () {
    [$organization, $admin] = orgAdminForAuditLog();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $response = SurveyResponse::factory()->create([
        'organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id,
    ]);

    $this->actingAs($admin)->deleteJson("/api/v1/surveys/{$survey->id}/responses/{$response->id}")->assertOk();

    $this->assertDatabaseHas('audit_logs', ['action' => 'response.deleted', 'actor_id' => $admin->id]);
});
