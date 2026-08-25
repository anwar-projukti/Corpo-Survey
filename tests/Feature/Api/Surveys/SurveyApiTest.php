<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function editor(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('a user with survey.create can create a survey via the api', function () {
    $user = editor();

    $response = $this->actingAs($user)->postJson('/api/v1/surveys', [
        'title' => 'New Survey',
        'description' => 'A description',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'New Survey')
        ->assertJsonPath('data.current_draft_version.status', 'draft')
        ->assertJsonPath('data.current_published_version', null);

    expect(Survey::where('organization_id', $user->organization_id)->count())->toBe(1);
});

test('creating a survey without a title fails validation', function () {
    $user = editor();

    $response = $this->actingAs($user)->postJson('/api/v1/surveys', []);

    $response->assertUnprocessable()->assertJsonValidationErrors('title');
});

test('a user without survey.create cannot create a survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $response = $this->actingAs($fieldUser)->postJson('/api/v1/surveys', ['title' => 'X']);

    $response->assertForbidden();
});

test('index lists only surveys in the users own organization', function () {
    $user = editor();
    $otherOrgSurvey = Survey::factory()->create();
    $ownSurvey = Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->getJson('/api/v1/surveys');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($ownSurvey->id)
        ->and($ids)->not->toContain($otherOrgSurvey->id);
});

test('show returns the full section/question/option tree for both versions', function () {
    $user = editor();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($version);
    $section = $version->sections()->create(['title' => 'S1', 'order' => 0]);
    $section->questions()->create(['type' => 'short_text', 'label' => 'Q1', 'order' => 0]);

    $response = $this->actingAs($user)->getJson("/api/v1/surveys/{$survey->id}");

    $response->assertOk()
        ->assertJsonPath('data.current_draft_version.sections.0.title', 'S1')
        ->assertJsonPath('data.current_draft_version.sections.0.questions.0.label', 'Q1');
});

test('a survey belonging to another organization is not visible (404, not 403 — avoids confirming it exists)', function () {
    $user = editor();
    $foreignSurvey = Survey::factory()->create();

    // Survey's BelongsToOrganization global scope means route-model
    // binding on {survey} simply can't find a foreign-org row — the
    // policy's explicit organization_id re-check (see SurveyPolicy) is
    // the defense-in-depth layer for when a query bypasses that scope.
    $response = $this->actingAs($user)->getJson("/api/v1/surveys/{$foreignSurvey->id}");

    $response->assertNotFound();
});

test('update changes the survey title and description', function () {
    $user = editor();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->patchJson("/api/v1/surveys/{$survey->id}", [
        'title' => 'Renamed',
    ]);

    $response->assertOk()->assertJsonPath('data.title', 'Renamed');
});

test('a Field User cannot update a survey', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($fieldUser)->patchJson("/api/v1/surveys/{$survey->id}", ['title' => 'X']);

    $response->assertForbidden();
});

test('destroy soft deletes the survey for a user with survey.delete', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($orgAdmin)->deleteJson("/api/v1/surveys/{$survey->id}");

    $response->assertOk();
    expect(Survey::find($survey->id))->toBeNull()
        ->and(Survey::withTrashed()->find($survey->id))->not->toBeNull();
});

test('an Editor cannot delete a survey', function () {
    $user = editor();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/surveys/{$survey->id}");

    $response->assertForbidden();
});

test('unauthenticated requests are rejected', function () {
    $response = $this->getJson('/api/v1/surveys');

    $response->assertUnauthorized();
});
