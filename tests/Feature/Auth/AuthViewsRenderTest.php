<?php

use App\Models\User;

test('the login view renders', function () {
    $this->get('/login')->assertOk()->assertSee('Log in');
});

test('the register view renders', function () {
    $this->get('/register')->assertOk()->assertSee('organization_name', escape: false);
});

test('the forgot password view renders', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('password reset link', escape: false);
});

test('the verify email view renders for an unverified user', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('Verify your email');
});

test('the confirm password view renders for an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/user/confirm-password')->assertOk()->assertSee('Confirm your password');
});

test('the two factor challenge view renders mid login', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->get('/two-factor-challenge')->assertOk()->assertSee('Two-factor authentication');
});
