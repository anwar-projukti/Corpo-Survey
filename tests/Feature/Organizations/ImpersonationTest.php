<?php

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;

// Every test below assumes enforcement is on, regardless of what
// TWO_FACTOR_ENFORCED happens to be in whichever .env this suite boots
// with — see tests/Feature/Auth/TwoFactorEnforcementTest.php.
beforeEach(fn () => config(['two-factor.enforced' => true]));

function confirmedSuperAdmin(): User
{
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    return $superAdmin;
}

test('a Super Admin can impersonate an organizations Org Admin', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($orgAdmin);
    expect(session('impersonator_id'))->toBe($superAdmin->id);
});

test('impersonating skips the impersonated accounts own 2FA requirement', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]); // no 2FA confirmed
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    $response = $this->get('/home');

    $response->assertOk();
});

test('a non-Super-Admin cannot impersonate', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $organization->id,
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('Org Admin');

    $other = Organization::factory()->create();
    User::factory()->create(['organization_id' => $other->id])->assignRole('Org Admin');

    $response = $this->actingAs($admin)->post("/organizations/{$other->id}/impersonate");

    $response->assertForbidden();
});

test('impersonating an organization with no Org Admin is rejected', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    $response->assertNotFound();
});

test('a Super Admin can return from impersonation back to their own account', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    $response = $this->delete('/impersonate');

    $response->assertRedirect(route('organizations.index'));
    $this->assertAuthenticatedAs($superAdmin);
    expect(session('impersonator_id'))->toBeNull();
});

test('ending impersonation without impersonating is rejected', function () {
    $superAdmin = confirmedSuperAdmin();

    $response = $this->actingAs($superAdmin)->delete('/impersonate');

    $response->assertStatus(409);
});

test('impersonating twice without returning first is rejected', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    // The session is now authenticated as the impersonated Org Admin, not
    // Super Admin, so this is denied by OrganizationPolicy (403) before
    // ever reaching the "already impersonating" 409 check in the
    // controller — which is the correct outcome either way: the request
    // can't succeed while impersonating regardless of which guard catches
    // it first.
    $response = $this->post("/organizations/{$organization->id}/impersonate");

    $response->assertForbidden();
});

test('impersonation does not stamp last_login_at on the impersonated account', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id, 'last_login_at' => null]);
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");

    expect($orgAdmin->fresh()->last_login_at)->toBeNull();
});

test('impersonation is recorded in the audit log on both start and end', function () {
    $superAdmin = confirmedSuperAdmin();
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($superAdmin)->post("/organizations/{$organization->id}/impersonate");
    $this->delete('/impersonate');

    $this->assertDatabaseHas(AuditLog::class, [
        'actor_id' => $superAdmin->id,
        'action' => 'organization.impersonation_started',
    ]);
    $this->assertDatabaseHas(AuditLog::class, [
        'actor_id' => $superAdmin->id,
        'action' => 'organization.impersonation_ended',
    ]);
});
