<?php

use App\Models\Organization;
use App\Models\User;

function orgAdminWithTeammateForEditDelete(string $role = 'Field User'): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    $teammate = User::factory()->create(['organization_id' => $organization->id, 'email' => 'teammate@example.com']);
    $teammate->assignRole($role);

    return [$organization, $admin, $teammate];
}

test('an Org Admin can update a teammates name and role', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();

    $response = $this->actingAs($admin)->patchJson("/api/v1/teammates/{$teammate->id}", [
        'name' => 'Renamed Teammate',
        'role' => 'Editor',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Renamed Teammate')
        ->assertJsonPath('data.role', 'Editor');

    expect($teammate->fresh()->hasRole('Editor'))->toBeTrue()
        ->and($teammate->fresh()->hasRole('Field User'))->toBeFalse();
});

test('an Org Admin can reset a teammates password', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();
    $originalHash = $teammate->password;

    $response = $this->actingAs($admin)->patchJson("/api/v1/teammates/{$teammate->id}", [
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $response->assertOk();
    expect($teammate->fresh()->password)->not->toBe($originalHash);
});

test('updating a teammate without a password leaves the existing password intact', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();
    $originalHash = $teammate->password;

    $this->actingAs($admin)->patchJson("/api/v1/teammates/{$teammate->id}", [
        'name' => 'Just A Rename',
    ])->assertOk();

    expect($teammate->fresh()->password)->toBe($originalHash);
});

test('an Org Admin cannot update another Org Admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');
    $otherAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $otherAdmin->assignRole('Org Admin');

    $this->actingAs($admin)->patchJson("/api/v1/teammates/{$otherAdmin->id}", [
        'name' => 'Hijacked',
    ])->assertForbidden();
});

test('an Org Admin cannot update a teammate in another organization', function () {
    [, $admin] = orgAdminWithTeammateForEditDelete();
    $otherOrg = Organization::factory()->create();
    $outsider = User::factory()->create(['organization_id' => $otherOrg->id]);
    $outsider->assignRole('Field User');

    $this->actingAs($admin)->patchJson("/api/v1/teammates/{$outsider->id}", [
        'name' => 'Hijacked',
    ])->assertForbidden();
});

test('an Org Admin can deactivate a teammate', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();

    $response = $this->actingAs($admin)->deleteJson("/api/v1/teammates/{$teammate->id}");

    $response->assertNoContent();
    expect(User::find($teammate->id))->toBeNull();
    expect(User::withTrashed()->find($teammate->id))->not->toBeNull();
});

test('a deactivated teammate can no longer log in', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();
    $teammate->forceFill(['password' => bcrypt('known-password')])->save();

    $this->actingAs($admin)->deleteJson("/api/v1/teammates/{$teammate->id}")->assertNoContent();

    // Laravel's default Eloquent user provider excludes soft-deleted rows
    // from the query login authenticates against — the real assertion
    // here is that the deactivated account simply can't be found by it
    // anymore, independent of whatever guard state this test's own prior
    // actingAs() call left behind.
    $provider = $this->app['auth']->createUserProvider(config('auth.guards.'.config('fortify.guard').'.provider'));

    expect($provider->retrieveByCredentials(['email' => 'teammate@example.com']))->toBeNull();
});

test('an Org Admin cannot deactivate another Org Admin', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');
    $otherAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $otherAdmin->assignRole('Org Admin');

    $this->actingAs($admin)->deleteJson("/api/v1/teammates/{$otherAdmin->id}")->assertForbidden();
    expect(User::find($otherAdmin->id))->not->toBeNull();
});

test('deactivating a teammate records an audit log entry', function () {
    [, $admin, $teammate] = orgAdminWithTeammateForEditDelete();

    $this->actingAs($admin)->deleteJson("/api/v1/teammates/{$teammate->id}");

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'user.deleted',
        'subject_id' => $teammate->id,
        'actor_id' => $admin->id,
    ]);
});
