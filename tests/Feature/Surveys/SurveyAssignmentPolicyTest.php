<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('an Org Admin can assign field users to a survey in their own organization', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($orgAdmin->can('assign', $survey))->toBeTrue();
});

test('an Editor can assign field users to a survey', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($editor->can('assign', $survey))->toBeTrue();
});

test('a Field User cannot assign field users to a survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($fieldUser->can('assign', $survey))->toBeFalse();
});

test('a user cannot assign field users to a survey belonging to a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);
    $orgAdminA->assignRole('Org Admin');
    $surveyB = Survey::factory()->create(['organization_id' => $orgB->id]);

    expect($orgAdminA->can('assign', $surveyB))->toBeFalse();
});

test('a Super Admin can assign field users to any organizations survey', function () {
    $organization = Organization::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($superAdmin->can('assign', $survey))->toBeTrue();
});
