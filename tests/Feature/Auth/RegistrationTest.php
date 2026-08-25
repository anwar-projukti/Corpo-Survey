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

    $response->assertRedirect('/home');
});

test('two organizations can share similarly named registrants without slug collisions', function () {
    $this->post('/register', [
        'name' => 'A',
        'organization_name' => 'Acme',
        'email' => 'a@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $this->post('/logout');

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

test('registration requires an organization name', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertSessionHasErrors('organization_name');
});
