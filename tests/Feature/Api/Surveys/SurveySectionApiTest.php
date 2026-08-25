<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function draftVersionFor(User $user): array
{
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($version);

    return [$survey, $version];
}

function editorUser(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('a section can be created on a draft version', function () {
    $user = editorUser();
    [, $version] = draftVersionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/sections", [
        'title' => 'Section One',
    ]);

    $response->assertCreated()->assertJsonPath('data.title', 'Section One');
});

test('creating a section on a published version is rejected', function () {
    $user = editorUser();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'published', 'published_by' => $user->id, 'published_at' => now()]);
    $survey->setCurrentPublishedVersion($version);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/sections", [
        'title' => 'Nope',
    ]);

    $response->assertForbidden();
});

test('new sections are appended after existing ones by order', function () {
    $user = editorUser();
    [, $version] = draftVersionFor($user);
    $version->sections()->create(['title' => 'First', 'order' => 0]);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/sections", [
        'title' => 'Second',
    ]);

    $response->assertCreated()->assertJsonPath('data.order', 1);
});

test('a section can be updated', function () {
    $user = editorUser();
    [, $version] = draftVersionFor($user);
    $section = $version->sections()->create(['title' => 'Old', 'order' => 0]);

    $response = $this->actingAs($user)->patchJson("/api/v1/survey-sections/{$section->id}", [
        'title' => 'New',
    ]);

    $response->assertOk()->assertJsonPath('data.title', 'New');
});

test('a section can be deleted', function () {
    $user = editorUser();
    [, $version] = draftVersionFor($user);
    $section = $version->sections()->create(['title' => 'Gone', 'order' => 0]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/survey-sections/{$section->id}");

    $response->assertOk();
    expect($version->sections()->count())->toBe(0);
});

test('sections can be reordered', function () {
    $user = editorUser();
    [, $version] = draftVersionFor($user);
    $s1 = $version->sections()->create(['title' => 'A', 'order' => 0]);
    $s2 = $version->sections()->create(['title' => 'B', 'order' => 1]);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/sections/reorder", [
        'order' => [$s2->id, $s1->id],
    ]);

    $response->assertOk();
    expect($version->sections()->orderBy('order')->pluck('id')->all())->toBe([$s2->id, $s1->id]);
});

test('editing a section on an archived version is rejected', function () {
    $user = editorUser();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'archived']);
    $section = $version->sections()->create(['title' => 'Old', 'order' => 0]);

    $response = $this->actingAs($user)->patchJson("/api/v1/survey-sections/{$section->id}", [
        'title' => 'New',
    ]);

    $response->assertForbidden();
});
