<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function confirmedSuperAdminForOrgApi(): User
{
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    return $superAdmin;
}

test('a Super Admin sees every organizations admin email in the list', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id, 'email' => 'boss@example.com']);
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/organizations');

    $response->assertOk();
    $payload = collect($response->json('data'))->firstWhere('id', $organization->id);
    expect($payload['admin_emails'])->toContain('boss@example.com');
});

test('an organization with no Org Admin has an empty admin_emails list', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/organizations');

    $payload = collect($response->json('data'))->firstWhere('id', $organization->id);
    expect($payload['admin_emails'])->toBe([]);
});

test('a Super Admin can delete an organization', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted($organization);
});

test('deleting an organization keeps its users and surveys intact', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}");

    expect(User::find($orgAdmin->id))->not->toBeNull()
        ->and(User::find($orgAdmin->id)->organization_id)->toBe($organization->id)
        ->and(Survey::withoutGlobalScopes()->find($survey->id))->not->toBeNull();
});

test('a deleted organization no longer appears in the list', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $organization->delete();

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/organizations');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('id'))->not->toContain($organization->id);
});

test('an Org Admin cannot delete an organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->deleteJson("/api/v1/organizations/{$organization->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'deleted_at' => null]);
});

test('the trashed list only shows soft-deleted organizations', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $active = Organization::factory()->create();
    $deleted = Organization::factory()->create();
    $deleted->delete();

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/organizations?trashed=1');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($deleted->id)->not->toContain($active->id);
});

test('a Super Admin can restore a deleted organization', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $organization->delete();

    $response = $this->actingAs($superAdmin)->postJson("/api/v1/organizations/{$organization->id}/restore");

    $response->assertOk();
    $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'deleted_at' => null]);
});

test('restoring an organization makes it reappear in the ordinary list', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $organization->delete();

    $this->actingAs($superAdmin)->postJson("/api/v1/organizations/{$organization->id}/restore");
    $response = $this->getJson('/api/v1/organizations');

    expect(collect($response->json('data'))->pluck('id'))->toContain($organization->id);
});

test('restoring an organization that is not deleted fails', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)->postJson("/api/v1/organizations/{$organization->id}/restore");

    $response->assertNotFound();
});

test('an Org Admin cannot restore a deleted organization', function () {
    $organization = Organization::factory()->create();
    $organization->delete();
    $admin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->postJson("/api/v1/organizations/{$organization->id}/restore");

    $response->assertForbidden();
});

test('a Super Admin can permanently delete an already-deleted organization', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $organization->delete();

    $response = $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    $response->assertNoContent();
    $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
});

test('permanently deleting an organization cascades to its surveys', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $organization->delete();

    $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    expect(Survey::withoutGlobalScopes()->find($survey->id))->toBeNull();
});

test('permanently deleting an organization also permanently deletes its remaining users', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id, 'email' => 'orphan@example.com']);
    $organization->delete();

    $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    // Not just soft-deleted: gone from the table entirely, so the email
    // is immediately free to register again. Leaving the user behind with
    // organization_id merely nulled (the FK's own nullOnDelete) was the
    // original bug — an org-less, role-less account that permanently
    // occupied its email in the unique index with no way to remove it.
    expect(User::withTrashed()->find($user->id))->toBeNull()
        ->and(User::withTrashed()->where('email', 'orphan@example.com')->exists())->toBeFalse();
});

test('an email can be registered again after its organization is permanently deleted', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id, 'email' => 'reuse@example.com']);
    $orgAdmin->assignRole('Org Admin');
    $organization->delete();

    $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    // /register carries Fortify's default 'guest' middleware — still
    // being authenticated as $superAdmin here would silently redirect
    // this request away before it ever reaches CreateNewUser.
    $this->post('/logout');

    $response = $this->post('/register', [
        'name' => 'New Owner',
        'organization_name' => 'Brand New Org',
        'email' => 'reuse@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionDoesntHaveErrors('email');
    $this->assertDatabaseHas('users', ['email' => 'reuse@example.com', 'name' => 'New Owner']);
});

test('force-deleting an organization that has not been soft-deleted first fails', function () {
    $superAdmin = confirmedSuperAdminForOrgApi();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    $response->assertNotFound();
    $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
});

test('an Org Admin cannot permanently delete an organization', function () {
    $organization = Organization::factory()->create();
    $organization->delete();
    $admin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->deleteJson("/api/v1/organizations/{$organization->id}/force");

    $response->assertForbidden();
    $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
});
