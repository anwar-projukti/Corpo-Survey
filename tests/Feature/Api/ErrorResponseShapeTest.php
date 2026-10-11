<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

/**
 * Spec §5.6/§11.2's consistent API error shape:
 * {"success": false, "message": "...", "errors": {...}} — applied
 * centrally in bootstrap/app.php's withExceptions() rather than
 * per-controller, so it must hold for every exception type an API client
 * can actually trigger.
 */
test('a validation error returns the success/message/errors envelope', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    $response = $this->actingAs($user)->postJson('/api/v1/surveys', []);

    $response->assertStatus(422)
        ->assertJson(['success' => false])
        ->assertJsonStructure(['success', 'message', 'errors']);
});

test('an unauthenticated JSON request returns the success/message envelope with no errors key', function () {
    $response = $this->getJson('/api/v1/surveys');

    $response->assertStatus(401)
        ->assertJson(['success' => false])
        ->assertJsonMissingPath('errors');
});

test('an unauthorized action returns the success/message envelope with no errors key', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/surveys/{$survey->id}");

    $response->assertStatus(403)
        ->assertJson(['success' => false])
        ->assertJsonMissingPath('errors');
});

test('a not found model returns the success/message envelope', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    $response = $this->actingAs($user)->getJson('/api/v1/surveys/999999');

    $response->assertStatus(404)
        ->assertJson(['success' => false])
        ->assertJsonMissingPath('errors');
});
