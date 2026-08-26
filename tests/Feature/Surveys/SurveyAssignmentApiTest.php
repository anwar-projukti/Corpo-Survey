<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('an Org Admin can assign a field user to a survey', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($orgAdmin)->postJson("/api/v1/surveys/{$survey->id}/assignments", [
        'user_id' => $fieldUser->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.id', $fieldUser->id)
        ->assertJsonPath('data.assigned_by.id', $orgAdmin->id);

    expect($survey->assignedFieldUsers()->count())->toBe(1);
});

test('assigning the same field user twice is rejected', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $survey->assignments()->create(['user_id' => $fieldUser->id, 'assigned_by' => $orgAdmin->id]);

    $response = $this->actingAs($orgAdmin)->postJson("/api/v1/surveys/{$survey->id}/assignments", [
        'user_id' => $fieldUser->id,
    ]);

    $response->assertStatus(422);
});

test('a user from another organization cannot be assigned to a survey', function () {
    $organization = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $outsider = User::factory()->create(['organization_id' => $otherOrg->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($orgAdmin)->postJson("/api/v1/surveys/{$survey->id}/assignments", [
        'user_id' => $outsider->id,
    ]);

    $response->assertStatus(422);
});

test('a Field User cannot assign anyone to a survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $otherFieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($fieldUser)->postJson("/api/v1/surveys/{$survey->id}/assignments", [
        'user_id' => $otherFieldUser->id,
    ]);

    $response->assertForbidden();
});

test('an Org Admin can list a surveys assignments', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $survey->assignments()->create(['user_id' => $fieldUser->id, 'assigned_by' => $orgAdmin->id]);

    $response = $this->actingAs($orgAdmin)->getJson("/api/v1/surveys/{$survey->id}/assignments");

    $response->assertOk()->assertJsonCount(1, 'data');
});

test('an Org Admin can remove an assignment', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $assignment = $survey->assignments()->create(['user_id' => $fieldUser->id, 'assigned_by' => $orgAdmin->id]);

    $response = $this->actingAs($orgAdmin)->deleteJson("/api/v1/surveys/{$survey->id}/assignments/{$assignment->id}");

    $response->assertOk();
    expect($survey->assignedFieldUsers()->count())->toBe(0);
});

test('an assignment belonging to a different survey cannot be deleted through this surveys endpoint', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $surveyA = Survey::factory()->create(['organization_id' => $organization->id]);
    $surveyB = Survey::factory()->create(['organization_id' => $organization->id]);
    $assignment = $surveyA->assignments()->create(['user_id' => $fieldUser->id, 'assigned_by' => $orgAdmin->id]);

    $response = $this->actingAs($orgAdmin)->deleteJson("/api/v1/surveys/{$surveyB->id}/assignments/{$assignment->id}");

    $response->assertNotFound();
    expect($surveyA->assignments()->count())->toBe(1);
});
