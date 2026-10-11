<?php

use App\Models\Organization;
use App\Models\User;

test('registering creates a new organization and its first user as Org Admin', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->organization_id)->not->toBeNull()
        ->and($user->hasRole('Org Admin'))->toBeTrue();

    $organization = Organization::find($user->organization_id);
    expect($organization->name)->toBe('Jane Research Co');

    // Registering no longer signs the user in automatically — they land
    // on the login screen and must sign in themselves (see
    // App\Http\Controllers\RegisterController, which replaces Fortify's
    // own registration flow for exactly this reason). Specifically
    // /org/login, not the plain /login — a freshly registered user is
    // always an Org Admin, and EnsureUserMatchesLoginPortal rejects an
    // Org Admin on the plain portal even with a correct password.
    $response->assertRedirect('/org/login');
    $this->assertGuest();
});

test('two organizations can share similarly named registrants without slug collisions', function () {
    $this->post('/register', [
        'name' => 'A',
        'organization_name' => 'Acme',
        'email' => 'a@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $this->post('/register', [
        'name' => 'B',
        'organization_name' => 'Acme',
        'email' => 'b@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    expect(Organization::where('name', 'Acme')->count())->toBe(2)
        ->and(Organization::pluck('slug')->unique()->count())->toBe(2);
});

test('a newly registered user does not need to verify their email before reaching the dashboard', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->hasVerifiedEmail())->toBeTrue();

    // The real proof this doesn't get stuck behind Fortify's "verify your
    // email" screen is a request to a 'verified'-gated route, signed in as
    // this user, actually succeeding.
    $response = $this->actingAs($user)->get('/home');

    $response->assertOk();
});

test('registering redirects to login with a success message instead of signing the user in', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertRedirect('/org/login');
    $response->assertSessionHas('status');
    $this->assertGuest();

    // Confirming credentials actually work when submitted through the
    // portal the user was actually redirected to — this is the exact
    // regression this test guards against: a correct password submitted
    // through the *wrong* portal (e.g. the plain /login form's implicit
    // portal=user) fails with "these credentials do not match our
    // records" even though the password is right.
    $user = User::where('email', 'jane@example.com')->first();
    $loginResponse = $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'portal' => 'org',
    ]);

    $loginResponse->assertRedirect('/home');
    $this->assertAuthenticatedAs($user);
});

test('a newly registered Org Admin cannot log in through the plain user portal', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    // The plain /login form defaults to portal=user when none is given —
    // exactly what a user following the register redirect would submit if
    // that redirect ever pointed at /login instead of /org/login again.
    $response = $this->post('/login', [
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('registration requires an organization name', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionHasErrors('organization_name');
});
