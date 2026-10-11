<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

test('a user can export their own account data', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'name' => 'Jane Doe']);
    Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/v1/me/data-export');

    $response->assertOk()
        ->assertJsonPath('data.account.name', 'Jane Doe')
        ->assertJsonPath('data.account.id', $user->id);
    expect($response->json('data.surveys_created'))->toHaveCount(1);
});

test('a user can anonymize their own account with the correct password', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'password' => bcrypt('correct-password')]);

    $response = $this->actingAs($user)->postJson('/api/v1/me/anonymize', ['password' => 'correct-password']);

    $response->assertOk()->assertJson(['success' => true]);

    $user->refresh();
    expect($user->name)->toBe('Deleted User');
    expect($user->email)->toContain('@anonymized.invalid');
    expect($user->status)->toBe('suspended');
});

test('anonymizing with the wrong password is rejected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'password' => bcrypt('correct-password')]);

    $response = $this->actingAs($user)->postJson('/api/v1/me/anonymize', ['password' => 'wrong-password']);

    $response->assertUnprocessable()->assertJsonValidationErrors(['password']);

    expect($user->refresh()->name)->not->toBe('Deleted User');
});

test('an anonymized account keeps its authored surveys attributable', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'password' => bcrypt('correct-password')]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $user->id]);

    $this->actingAs($user)->postJson('/api/v1/me/anonymize', ['password' => 'correct-password'])->assertOk();

    expect($survey->fresh()->created_by)->toBe($user->id);
});

test('an anonymized account has its access tokens revoked', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'password' => bcrypt('correct-password')]);
    $user->createToken('test-token');

    $this->actingAs($user)->postJson('/api/v1/me/anonymize', ['password' => 'correct-password'])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
