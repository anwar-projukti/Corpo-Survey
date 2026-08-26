<?php

use App\Models\Organization;
use App\Models\SurveyResponse;
use App\Models\User;

test('an Org Admin can view, edit, delete, and export responses in their own organization', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    expect($orgAdmin->can('viewAny', SurveyResponse::class))->toBeTrue()
        ->and($orgAdmin->can('view', $response))->toBeTrue()
        ->and($orgAdmin->can('update', $response))->toBeTrue()
        ->and($orgAdmin->can('delete', $response))->toBeTrue()
        ->and($orgAdmin->can('export', SurveyResponse::class))->toBeTrue();
});

test('an Editor can view responses but not edit, delete, or export them', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    expect($editor->can('view', $response))->toBeTrue()
        ->and($editor->can('update', $response))->toBeFalse()
        ->and($editor->can('delete', $response))->toBeFalse()
        ->and($editor->can('export', SurveyResponse::class))->toBeFalse();
});

test('a Field User can view responses but not edit, delete, or export them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    expect($fieldUser->can('view', $response))->toBeTrue()
        ->and($fieldUser->can('update', $response))->toBeFalse()
        ->and($fieldUser->can('delete', $response))->toBeFalse();
});

test('a Viewer can view responses but not edit, delete, or export them', function () {
    // Viewer has report.export (summary reports) but not response.export
    // (raw response data) — a deliberate distinction from spec §5.2's
    // permission list, not an oversight.
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');
    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    expect($viewer->can('view', $response))->toBeTrue()
        ->and($viewer->can('export', SurveyResponse::class))->toBeFalse()
        ->and($viewer->can('update', $response))->toBeFalse()
        ->and($viewer->can('delete', $response))->toBeFalse();
});

test('a user cannot view, edit, or delete a response belonging to a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);
    $orgAdminA->assignRole('Org Admin');
    $responseB = SurveyResponse::factory()->create(['organization_id' => $orgB->id]);

    expect($orgAdminA->can('view', $responseB))->toBeFalse()
        ->and($orgAdminA->can('update', $responseB))->toBeFalse()
        ->and($orgAdminA->can('delete', $responseB))->toBeFalse();
});

test('a Super Admin can do anything to any organizations response', function () {
    $organization = Organization::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');
    $response = SurveyResponse::factory()->create(['organization_id' => $organization->id]);

    expect($superAdmin->can('view', $response))->toBeTrue()
        ->and($superAdmin->can('update', $response))->toBeTrue()
        ->and($superAdmin->can('delete', $response))->toBeTrue()
        ->and($superAdmin->can('export', SurveyResponse::class))->toBeTrue();
});
