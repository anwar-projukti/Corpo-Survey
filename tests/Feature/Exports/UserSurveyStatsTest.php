<?php

use App\Enums\CollectionMethod;
use App\Jobs\RefreshUserSurveyStatsJob;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Models\UserSurveyStat;

function orgAdminForStats(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

test('the nightly job rebuilds user_survey_stats from response counts', function () {
    [$organization] = orgAdminForStats();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);

    SurveyResponse::factory()->count(3)->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collected_by_user_id' => $fieldUser->id,
        'collection_method' => CollectionMethod::FieldAgent,
        'submitted_at' => now(),
    ]);

    (new RefreshUserSurveyStatsJob)->handle();

    $stat = UserSurveyStat::query()->where('user_id', $fieldUser->id)->where('survey_id', $survey->id)->first();
    expect($stat)->not->toBeNull();
    expect($stat->response_count)->toBe(3);
});

test('the nightly job excludes self serve responses with no collector', function () {
    [$organization] = orgAdminForStats();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);

    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
        'collection_method' => CollectionMethod::SelfServe,
    ]);

    (new RefreshUserSurveyStatsJob)->handle();

    expect(UserSurveyStat::query()->count())->toBe(0);
});

test('an Org Admin can view one users stats across surveys', function () {
    [$organization, $admin] = orgAdminForStats();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    UserSurveyStat::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $fieldUser->id,
        'survey_id' => $survey->id,
        'response_count' => 42,
    ]);

    $result = $this->actingAs($admin)->getJson("/api/v1/users/{$fieldUser->id}/stats");

    $result->assertOk()->assertJsonPath('data.0.response_count', 42);
});

test('an Org Admin can view the org wide user stats ranking', function () {
    [$organization, $admin] = orgAdminForStats();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    UserSurveyStat::factory()->count(2)->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    $result = $this->actingAs($admin)->getJson("/api/v1/organizations/{$organization->id}/user-stats");

    $result->assertOk()->assertJsonCount(2, 'data');
});

test('a Field User without report.view cannot see the org wide user stats ranking', function () {
    [$organization, $admin] = orgAdminForStats();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $this->actingAs($fieldUser)->getJson("/api/v1/organizations/{$organization->id}/user-stats")->assertForbidden();
});

test('a user cannot view stats for another organization', function () {
    [, $admin] = orgAdminForStats();
    $otherOrg = Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);

    $this->actingAs($admin)->getJson("/api/v1/users/{$otherUser->id}/stats")->assertForbidden();
    $this->actingAs($admin)->getJson("/api/v1/organizations/{$otherOrg->id}/user-stats")->assertForbidden();
});
