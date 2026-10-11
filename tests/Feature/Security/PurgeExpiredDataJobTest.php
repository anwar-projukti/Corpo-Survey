<?php

use App\Jobs\PurgeExpiredDataJob;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Services\AuditLogger;

test('a survey response soft deleted past the retention window is hard deleted', function () {
    config(['retention.soft_delete_days' => 90]);

    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $response = SurveyResponse::factory()->create([
        'organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id,
    ]);
    $response->delete();
    $response->forceFill(['deleted_at' => now()->subDays(100)])->saveQuietly();

    app(PurgeExpiredDataJob::class)->handle(app(AuditLogger::class));

    expect(SurveyResponse::withTrashed()->find($response->id))->toBeNull();
});

test('a survey response soft deleted within the retention window is kept', function () {
    config(['retention.soft_delete_days' => 90]);

    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $response = SurveyResponse::factory()->create([
        'organization_id' => $organization->id, 'survey_id' => $survey->id, 'survey_version_id' => $version->id,
    ]);
    $response->delete();
    $response->forceFill(['deleted_at' => now()->subDays(10)])->saveQuietly();

    app(PurgeExpiredDataJob::class)->handle(app(AuditLogger::class));

    expect(SurveyResponse::withTrashed()->find($response->id))->not->toBeNull();
});

test('the purge run is itself audited', function () {
    config(['retention.soft_delete_days' => 90]);

    app(PurgeExpiredDataJob::class)->handle(app(AuditLogger::class));

    $this->assertDatabaseHas('audit_logs', ['action' => 'data.retention_purge']);
});
