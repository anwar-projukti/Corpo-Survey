<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

/**
 * Locks in the exact response envelope shape resources/js/surveys/api.js
 * depends on. A single-resource endpoint must return {"data": {...}} (so
 * api.js's `.then(r => r.data.data)` unwraps correctly) and a list
 * endpoint must return {"data": [...], "links": ..., "meta": ...} (so
 * api.js's `surveys.list()` callers read `.data` for the array). Getting
 * this wrong once already caused a real bug: the builder read `data.id`
 * on the wrapped envelope instead of `data.data.id`, sending the browser
 * to `/surveys/undefined` after every successful creation.
 */
function apiUser(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('creating a survey returns a single wrapped resource, not a paginated envelope', function () {
    $user = apiUser();

    $response = $this->actingAs($user)->postJson('/api/v1/surveys', ['title' => 'Shape Test']);

    $response->assertCreated();
    expect($response->json('data.id'))->toBeInt()
        ->and($response->json('data.title'))->toBe('Shape Test')
        ->and($response->json('links'))->toBeNull()
        ->and($response->json('meta'))->toBeNull();
});

test('listing surveys returns a paginated envelope with data/links/meta', function () {
    $user = apiUser();
    Survey::factory()->create(['organization_id' => $user->organization_id]);

    $response = $this->actingAs($user)->getJson('/api/v1/surveys');

    $response->assertOk();
    expect($response->json('data'))->toBeArray()
        ->and($response->json('meta.current_page'))->toBe(1)
        ->and($response->json('links.first'))->not->toBeNull();
});

test('publishing a survey returns a single wrapped resource', function () {
    $user = apiUser();
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $version->sections()->create(['title' => 'S', 'order' => 0])
        ->questions()->create(['type' => 'short_text', 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentDraftVersion($version);

    $response = $this->actingAs($user)->postJson("/api/v1/surveys/{$survey->id}/publish");

    $response->assertOk();
    expect($response->json('data.id'))->toBe($survey->id)
        ->and($response->json('links'))->toBeNull();
});
