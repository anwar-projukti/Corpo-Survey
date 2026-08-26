<?php

use App\Models\User;

// Every test below assumes enforcement is on, regardless of what
// TWO_FACTOR_ENFORCED happens to be in whichever .env this suite boots
// with (a local dev override, e.g. for testing without an authenticator
// app, would otherwise leak into these and break them).
beforeEach(fn () => config(['two-factor.enforced' => true]));

test('two-factor enforcement can be disabled via config for local development', function () {
    config(['two-factor.enforced' => false]);

    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get('/home');

    $response->assertOk();
});

test('an Org Admin without 2FA confirmed is redirected to two-factor setup', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get('/home');

    $response->assertRedirect(route('two-factor.setup'));
});

test('a Super Admin without 2FA confirmed is redirected to two-factor setup', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    $response = $this->actingAs($superAdmin)->get('/home');

    $response->assertRedirect(route('two-factor.setup'));
});

test('an Org Admin with 2FA confirmed can access the dashboard', function () {
    $orgAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get('/home');

    $response->assertOk();
});

test('a non privileged role is never forced into two-factor setup', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $response = $this->actingAs($editor)->get('/home');

    $response->assertOk();
});

test('an Org Admin without 2FA can still reach the two-factor setup route itself', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get('/two-factor-setup');

    $response->assertOk();
});

test('an Org Admin without 2FA can reach the password confirmation screen without a redirect loop', function () {
    // Fortify's two-factor.enable/confirm routes require a freshly confirmed
    // password and redirect to password.confirm when it isn't; that route
    // must stay reachable or RequireTwoFactorForPrivilegedRoles would bounce
    // it straight back to two-factor.setup forever.
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get('/user/confirm-password');

    $response->assertOk();
});

test('an Org Admin without 2FA can reach the confirm-password redirect route the setup page uses', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->get(route('two-factor.setup.confirm-password'));

    $response->assertRedirect(route('password.confirm'));
});

test('confirming a password via the two-factor confirm-password redirect route sends the user back to two-factor setup', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($orgAdmin)->get(route('two-factor.setup.confirm-password'));

    $response = $this->actingAs($orgAdmin)->post(route('password.confirm.store'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.setup'));
});
