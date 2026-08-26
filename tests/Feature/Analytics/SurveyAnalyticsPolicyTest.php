<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('an Org Admin can view a surveys analytics', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($orgAdmin->can('viewReports', $survey))->toBeTrue();
});

test('a Viewer can view a surveys analytics', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($viewer->can('viewReports', $survey))->toBeTrue();
});

test('a Field User cannot view a surveys analytics', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($fieldUser->can('viewReports', $survey))->toBeFalse();
});

test('a user cannot view analytics for a survey belonging to a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);
    $orgAdminA->assignRole('Org Admin');
    $surveyB = Survey::factory()->create(['organization_id' => $orgB->id]);

    expect($orgAdminA->can('viewReports', $surveyB))->toBeFalse();
});

test('a Super Admin can view analytics for any organizations survey', function () {
    $organization = Organization::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($superAdmin->can('viewReports', $survey))->toBeTrue();
});
