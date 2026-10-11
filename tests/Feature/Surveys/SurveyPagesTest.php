<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function editorForPages(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('the survey list page renders for a user with survey.view', function () {
    $user = editorForPages();

    $response = $this->actingAs($user)->get('/surveys');

    $response->assertOk()->assertSee(__('surveys.builder.all_surveys'));
});

test('a user without survey.view cannot access the survey list page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($user)->get('/surveys');

    $response->assertForbidden();
});

test('the survey builder page renders for a survey in the users organization', function () {
    $user = editorForPages();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->get("/surveys/{$survey->id}");

    $response->assertOk()->assertSee($survey->title, escape: false);
});

test('the survey builder page is not accessible for a survey in another organization', function () {
    $user = editorForPages();
    $foreignSurvey = Survey::factory()->create();

    $response = $this->actingAs($user)->get("/surveys/{$foreignSurvey->id}");

    $response->assertNotFound();
});

test('the builder page renders in Bangla when the locale is bn', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'locale' => 'bn']);
    $user->assignRole('Editor');

    $response = $this->actingAs($user)->get('/surveys');

    $response->assertOk()->assertSee('সব জরিপ');
});

test('a Field User cannot access the survey list page even though they have survey.view', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $response = $this->actingAs($fieldUser)->get('/surveys');

    $response->assertForbidden();
});

test('a Field User cannot access the survey builder page even for an assigned survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignedSurveys()->attach($survey);

    $response = $this->actingAs($fieldUser)->get("/surveys/{$survey->id}");

    $response->assertForbidden();
});

test('an Org Admin who also holds Field User still gets full builder access', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole(['Org Admin', 'Field User']);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($user)->get("/surveys/{$survey->id}");

    $response->assertOk()->assertSee($survey->title, escape: false);
});

test('the Create Survey button is hidden from a Viewer without survey.create', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');

    $response = $this->actingAs($viewer)->get('/surveys');

    $response->assertOk()->assertDontSee(__('surveys.builder.create_survey'));
});

test('the Create Survey button is shown to an Editor with survey.create', function () {
    $user = editorForPages();

    $response = $this->actingAs($user)->get('/surveys');

    $response->assertOk()->assertSee(__('surveys.builder.create_survey'));
});

test('a Viewer sees the builder read-only: no Manage Field Users trigger, and surveyBuilder gets canEdit=false hiding Publish/edit controls client-side', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($viewer)->get("/surveys/{$survey->id}");

    // Checking the click handler rather than the translated label text —
    // "Field users" (surveys.builder.manage_field_users) is also a
    // substring of surveys.builder.field_users_body, which the assignments
    // modal always embeds regardless of role, making assertDontSee on the
    // translated string a false negative.
    $response->assertOk()
        ->assertDontSee('openAssignments()', escape: false)
        ->assertSee("surveyBuilder({$survey->id}, false)", escape: false);
});

test('an Editor with survey.edit gets canEdit=true from surveyBuilder', function () {
    $user = editorForPages();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->get("/surveys/{$survey->id}");

    $response->assertOk()->assertSee("surveyBuilder({$survey->id}, true)", escape: false);
});
