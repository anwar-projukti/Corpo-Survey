<?php

use App\Models\Organization;
use App\Models\User;

function orgAdminForTeammates(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

test('an Org Admin can create a teammate with an assignable role', function () {
    [$organization, $admin] = orgAdminForTeammates();

    $result = $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Field User',
    ]);

    $result->assertCreated()
        ->assertJsonPath('data.name', 'Jamie Field')
        ->assertJsonPath('data.email', 'jamie@example.com')
        ->assertJsonPath('data.role', 'Field User');

    $teammate = User::where('email', 'jamie@example.com')->first();
    expect($teammate)->not->toBeNull()
        ->and($teammate->organization_id)->toBe($organization->id)
        ->and($teammate->hasRole('Field User'))->toBeTrue()
        ->and($teammate->email_verified_at)->not->toBeNull();
});

test('a new teammate can log in immediately with the password the Org Admin set', function () {
    [, $admin] = orgAdminForTeammates();

    $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Field User',
    ])->assertCreated();

    $response = $this->post('/login', [
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticated();
});

test('creating a teammate as Org Admin is rejected', function () {
    [, $admin] = orgAdminForTeammates();

    $result = $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Another Admin',
        'email' => 'another-admin@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Org Admin',
    ]);

    $result->assertUnprocessable()->assertJsonValidationErrors(['role']);
    $this->assertDatabaseMissing('users', ['email' => 'another-admin@example.com']);
});

test('creating a teammate as Super Admin is rejected', function () {
    [, $admin] = orgAdminForTeammates();

    $result = $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Super Admin',
    ]);

    $result->assertUnprocessable()->assertJsonValidationErrors(['role']);
    $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
});

test('a user without user.manage cannot create a teammate', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');

    $result = $this->actingAs($editor)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Field User',
    ]);

    $result->assertForbidden();
});

test('a Field User cannot create a teammate', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $result = $this->actingAs($fieldUser)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Field User',
    ]);

    $result->assertForbidden();
});

test('duplicate email is rejected', function () {
    [$organization, $admin] = orgAdminForTeammates();
    User::factory()->create(['organization_id' => $organization->id, 'email' => 'taken@example.com']);

    $result = $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'taken@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'correct-password',
        'role' => 'Field User',
    ]);

    $result->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

test('mismatched password confirmation is rejected', function () {
    [, $admin] = orgAdminForTeammates();

    $result = $this->actingAs($admin)->postJson('/api/v1/teammates', [
        'name' => 'Jamie Field',
        'email' => 'jamie@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'a-different-password',
        'role' => 'Field User',
    ]);

    $result->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

test('an Org Admin can list only their own organizations teammates', function () {
    [$organization, $admin] = orgAdminForTeammates();
    $ownTeammate = User::factory()->create(['organization_id' => $organization->id]);
    $ownTeammate->assignRole('Field User');

    $otherOrg = Organization::factory()->create();
    $otherTeammate = User::factory()->create(['organization_id' => $otherOrg->id]);
    $otherTeammate->assignRole('Field User');

    $result = $this->actingAs($admin)->getJson('/api/v1/teammates');

    $result->assertOk();
    $emails = collect($result->json('data'))->pluck('email');
    expect($emails)->toContain($admin->email, $ownTeammate->email)
        ->and($emails)->not->toContain($otherTeammate->email);
});

test('the users page renders for an Org Admin', function () {
    [, $admin] = orgAdminForTeammates();

    $response = $this->actingAs($admin)->get('/users');

    $response->assertOk()->assertSee(__('surveys.team.title'));
});

test('a Field User cannot access the users page', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $response = $this->actingAs($fieldUser)->get('/users');

    $response->assertForbidden();
});
