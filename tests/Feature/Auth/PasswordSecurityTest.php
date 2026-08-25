<?php

use App\Models\AuditLog;
use App\Models\User;

test('updating a password rotates the remember token and is audited', function () {
    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
        'remember_token' => 'stale-token',
    ]);

    $this->actingAs($user)->put('/user/password', [
        'current_password' => 'old-password',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $user->refresh();

    expect($user->remember_token)->not->toBe('stale-token')
        ->and(AuditLog::where('action', 'password.updated')->where('actor_id', $user->id)->exists())->toBeTrue();
});

test('resetting a forgotten password rotates the remember token and is audited', function () {
    $user = User::factory()->create([
        'remember_token' => 'stale-token',
    ]);

    $token = app('auth.password.broker')->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $user->refresh();

    expect($user->remember_token)->not->toBe('stale-token')
        ->and(AuditLog::where('action', 'password.reset')->where('actor_id', $user->id)->exists())->toBeTrue();
});
