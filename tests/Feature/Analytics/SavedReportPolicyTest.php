<?php

use App\Models\Organization;
use App\Models\SavedReport;
use App\Models\Survey;
use App\Models\User;

test('an Org Admin can create update and delete a saved report', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $report = SavedReport::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    expect($admin->can('create', [SavedReport::class, $survey]))->toBeTrue()
        ->and($admin->can('update', $report))->toBeTrue()
        ->and($admin->can('delete', $report))->toBeTrue()
        ->and($admin->can('view', $report))->toBeTrue();
});

test('a Viewer can create and export saved reports but an Editor cannot create them', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($viewer->can('create', [SavedReport::class, $survey]))->toBeTrue()
        ->and($editor->can('create', [SavedReport::class, $survey]))->toBeFalse()
        ->and($editor->can('viewAny', [SavedReport::class, $survey]))->toBeTrue();
});

test('a Field User cannot view or create saved reports', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($fieldUser->can('viewAny', [SavedReport::class, $survey]))->toBeFalse()
        ->and($fieldUser->can('create', [SavedReport::class, $survey]))->toBeFalse();
});

test('a user cannot manage a saved report belonging to a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    $adminA->assignRole('Org Admin');
    $surveyB = Survey::factory()->create(['organization_id' => $orgB->id]);
    $reportB = SavedReport::factory()->create(['organization_id' => $orgB->id, 'survey_id' => $surveyB->id]);

    expect($adminA->can('view', $reportB))->toBeFalse()
        ->and($adminA->can('update', $reportB))->toBeFalse()
        ->and($adminA->can('delete', $reportB))->toBeFalse()
        ->and($adminA->can('create', [SavedReport::class, $surveyB]))->toBeFalse();
});

test('a Super Admin can manage saved reports for any organization', function () {
    $organization = Organization::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $report = SavedReport::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    expect($superAdmin->can('create', [SavedReport::class, $survey]))->toBeTrue()
        ->and($superAdmin->can('update', $report))->toBeTrue()
        ->and($superAdmin->can('delete', $report))->toBeTrue();
});
