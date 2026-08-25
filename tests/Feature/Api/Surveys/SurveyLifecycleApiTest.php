<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function orgAdmin(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    return $user;
}

test('publishing a draft with at least one question succeeds', function () {
    $user = orgAdmin();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $version->sections()->create(['title' => 'S', 'order' => 0])
        ->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentDraftVersion($version);

    $response = $this->actingAs($user)->postJson("/api/v1/surveys/{$survey->id}/publish");

    $response->assertOk()
        ->assertJsonPath('data.current_published_version.status', 'published')
        ->assertJsonPath('data.current_draft_version', null);
});

test('publishing an empty draft fails with a 422', function () {
    $user = orgAdmin();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($version);

    $response = $this->actingAs($user)->postJson("/api/v1/surveys/{$survey->id}/publish");

    $response->assertUnprocessable();
});

test('an Editor can publish (has survey.publish) but a Field User cannot', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $version->sections()->create(['title' => 'S', 'order' => 0])
        ->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentDraftVersion($version);

    $this->actingAs($fieldUser)->postJson("/api/v1/surveys/{$survey->id}/publish")->assertForbidden();
    $this->actingAs($editor)->postJson("/api/v1/surveys/{$survey->id}/publish")->assertOk();
});

test('creating a new draft from a published survey clones its content', function () {
    $user = orgAdmin();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'published', 'published_by' => $user->id, 'published_at' => now()]);
    $version->sections()->create(['title' => 'Original Section', 'order' => 0])
        ->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentPublishedVersion($version);

    $response = $this->actingAs($user)->postJson("/api/v1/surveys/{$survey->id}/draft");

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.version_number', 2)
        ->assertJsonPath('data.sections.0.title', 'Original Section');
});

test('creating a new draft fails if one already exists', function () {
    $user = orgAdmin();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'published', 'published_by' => $user->id, 'published_at' => now()]);
    $version->sections()->create(['title' => 'S', 'order' => 0]);
    $survey->setCurrentPublishedVersion($version);

    $draft = $survey->versions()->create(['version_number' => 2, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($draft);

    $response = $this->actingAs($user)->postJson("/api/v1/surveys/{$survey->id}/draft");

    $response->assertUnprocessable();
});
