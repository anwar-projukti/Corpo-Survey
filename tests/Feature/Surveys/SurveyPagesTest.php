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
