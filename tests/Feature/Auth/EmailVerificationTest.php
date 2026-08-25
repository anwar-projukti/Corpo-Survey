<?php

use App\Models\User;

test('an unverified user is redirected away from the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/home');

    $response->assertRedirect(route('verification.notice'));
});

test('a verified user can access the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/home');

    $response->assertOk();
});
