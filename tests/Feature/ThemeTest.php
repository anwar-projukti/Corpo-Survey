<?php

use App\Models\User;

test('a logged in user can persist their theme preference', function () {
    $user = User::factory()->create(['theme_preference' => 'system']);

    $response = $this->actingAs($user)->postJson('/theme', ['theme' => 'dark']);

    $response->assertOk();
    expect($user->fresh()->theme_preference)->toBe('dark');
});

test('an invalid theme value is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/theme', ['theme' => 'purple']);

    $response->assertStatus(422);
});

test('the theme route requires authentication', function () {
    $response = $this->postJson('/theme', ['theme' => 'dark']);

    $response->assertUnauthorized();
});

test('the inline theme script reflects a logged in users stored preference', function () {
    $user = User::factory()->create(['theme_preference' => 'dark']);

    $response = $this->actingAs($user)->get('/home');

    $response->assertSee('window.__themePreference = "dark"', escape: false);
});

test('a guest sees no server-side theme preference in the inline script', function () {
    $response = $this->get('/login');

    $response->assertSee('window.__themePreference = null', escape: false);
});

test('a privileged user without confirmed 2FA gets a JSON error from theme, not an HTML redirect', function () {
    // RequireTwoFactorForPrivilegedRoles would otherwise redirect this
    // request to two-factor.setup; a JSON-expecting caller must get a JSON
    // response back so the failure is visible instead of silently followed.
    $orgAdmin = User::factory()->create(['theme_preference' => 'system']);
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->postJson('/theme', ['theme' => 'dark']);

    $response->assertStatus(409);
    expect($orgAdmin->fresh()->theme_preference)->toBe('system');
});
