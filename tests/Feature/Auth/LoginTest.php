<?php

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;

test('a user can log in with correct credentials', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($user);
});

test('login fails with incorrect credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});

test('successful login regenerates the session id', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $this->get('/login');
    $originalSessionId = session()->getId();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    expect(session()->getId())->not->toBe($originalSessionId);
});

test('successful login writes an audit log entry', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    expect(AuditLog::where('action', 'login')->where('actor_id', $user->id)->exists())->toBeTrue();
});

test('failed login writes an audit log entry', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    expect(
        AuditLog::where('action', 'login.failed')->where('actor_email', $user->email)->exists()
    )->toBeTrue();
});

test('successful login stamps the absolute session start time immediately', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    // Set at real login time by LogSuccessfulLogin, not lazily by
    // EnsureSessionIsNotExpired on some later request.
    expect(session('auth.session_started_at'))->not->toBeNull()
        ->and(session('auth.session_started_at'))->toBeLessThanOrEqual(now()->timestamp);
});

test('successful login records last_login_at and last_login_ip', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    expect($user->last_login_at)->toBeNull();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->not->toBeNull();
});

test('a logged in user can log out and it is audited', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    expect(AuditLog::where('action', 'logout')->where('actor_id', $user->id)->exists())->toBeTrue();
});

test('repeated failed logins trigger progressive lockout even with the correct password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    // Our progressive throttle (keyed on email+ip) locks out after 5
    // failures, independently of Fortify's own flat per-minute limiter.
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertStatus(429);
    $this->assertGuest();
});
