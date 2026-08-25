<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('an Org Admin can view, create, edit, delete, and publish surveys in their own organization', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $orgAdmin->id]);

    expect($orgAdmin->can('viewAny', Survey::class))->toBeTrue()
        ->and($orgAdmin->can('create', Survey::class))->toBeTrue()
        ->and($orgAdmin->can('view', $survey))->toBeTrue()
        ->and($orgAdmin->can('update', $survey))->toBeTrue()
        ->and($orgAdmin->can('delete', $survey))->toBeTrue()
        ->and($orgAdmin->can('publish', $survey))->toBeTrue();
});

test('a Field User can view surveys but cannot create, edit, delete, or publish them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($fieldUser->can('view', $survey))->toBeTrue()
        ->and($fieldUser->can('create', Survey::class))->toBeFalse()
        ->and($fieldUser->can('update', $survey))->toBeFalse()
        ->and($fieldUser->can('delete', $survey))->toBeFalse()
        ->and($fieldUser->can('publish', $survey))->toBeFalse();
});

test('an Editor can view, create, edit, and publish surveys but not delete them', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    expect($editor->can('create', Survey::class))->toBeTrue()
        ->and($editor->can('update', $survey))->toBeTrue()
        ->and($editor->can('publish', $survey))->toBeTrue()
        ->and($editor->can('delete', $survey))->toBeFalse();
});

test('a user cannot view, edit, delete, or publish a survey belonging to a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);
    $orgAdminA->assignRole('Org Admin');

    // Uses the factory (which bypasses mass-assignment guarding) to
    // simulate a policy check on a survey instance that somehow reached
    // this code path despite belonging to another org — the scope should
    // never let this happen via a normal query, but the policy is the
    // last line of defense regardless.
    $surveyB = Survey::factory()->create([
        'organization_id' => $orgB->id,
        'created_by' => User::factory()->create(['organization_id' => $orgB->id])->id,
        'title' => 'Org B survey',
        'slug' => 'org-b-survey',
    ]);

    expect($orgAdminA->can('view', $surveyB))->toBeFalse()
        ->and($orgAdminA->can('update', $surveyB))->toBeFalse()
        ->and($orgAdminA->can('delete', $surveyB))->toBeFalse()
        ->and($orgAdminA->can('publish', $surveyB))->toBeFalse();
});

test('a Super Admin can do anything to any organizations survey', function () {
    $organization = Organization::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    $survey = Survey::factory()->create([
        'organization_id' => $organization->id,
        'created_by' => User::factory()->create(['organization_id' => $organization->id])->id,
        'title' => 'Some survey',
        'slug' => 'some-survey',
    ]);

    expect($superAdmin->can('view', $survey))->toBeTrue()
        ->and($superAdmin->can('update', $survey))->toBeTrue()
        ->and($superAdmin->can('delete', $survey))->toBeTrue()
        ->and($superAdmin->can('publish', $survey))->toBeTrue();
});
