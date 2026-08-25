<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('a version can be viewed by a user in the same organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);

    $response = $this->actingAs($user)->getJson("/api/v1/survey-versions/{$version->id}");

    $response->assertOk()->assertJsonPath('data.id', $version->id);
});

test('a user from another organization gets 403, not a crash, viewing a foreign version', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    $foreignOrg = Organization::factory()->create();
    $foreignSurvey = Survey::factory()->create(['organization_id' => $foreignOrg->id]);
    $foreignVersion = $foreignSurvey->versions()->create(['version_number' => 1, 'status' => 'draft']);

    $response = $this->actingAs($user)->getJson("/api/v1/survey-versions/{$foreignVersion->id}");

    $response->assertForbidden();
});

test('a user without survey.view cannot view a version even in their own organization', function () {
    // No role assigned at all, so no permissions.
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);

    $response = $this->actingAs($user)->getJson("/api/v1/survey-versions/{$version->id}");

    $response->assertForbidden();
});
