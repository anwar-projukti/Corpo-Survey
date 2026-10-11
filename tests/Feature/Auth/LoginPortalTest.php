<?php

use App\Models\Organization;
use App\Models\User;

test('a Super Admin can log in through the admin portal', function () {
    $superAdmin = User::factory()->superAdmin()->create(['password' => bcrypt('correct-password')]);
    $superAdmin->assignRole('Super Admin');

    $this->get('/admin/login')->assertOk();

    $response = $this->post('/login', [
        'email' => $superAdmin->email,
        'password' => 'correct-password',
        'portal' => 'admin',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($superAdmin);
});

test('a Super Admin cannot log in through the org or user portal', function () {
    $superAdmin = User::factory()->superAdmin()->create(['password' => bcrypt('correct-password')]);
    $superAdmin->assignRole('Super Admin');

    foreach (['org', 'user'] as $portal) {
        $response = $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'correct-password',
            'portal' => $portal,
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
});

test('an Org Admin can log in through the org portal', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => bcrypt('correct-password'),
    ]);
    $orgAdmin->assignRole('Org Admin');

    $this->get('/org/login')->assertOk();

    $response = $this->post('/login', [
        'email' => $orgAdmin->email,
        'password' => 'correct-password',
        'portal' => 'org',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($orgAdmin);
});

test('an Org Admin cannot log in through the admin or user portal', function () {
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => bcrypt('correct-password'),
    ]);
    $orgAdmin->assignRole('Org Admin');

    foreach (['admin', 'user'] as $portal) {
        $response = $this->post('/login', [
            'email' => $orgAdmin->email,
            'password' => 'correct-password',
            'portal' => $portal,
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
});

test('a Field User can log in through the default user portal', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => bcrypt('correct-password'),
    ]);
    $fieldUser->assignRole('Field User');

    $response = $this->post('/login', [
        'email' => $fieldUser->email,
        'password' => 'correct-password',
        'portal' => 'user',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs($fieldUser);
});

test('a Field User cannot log in through the admin or org portal', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create([
        'organization_id' => $organization->id,
        'password' => bcrypt('correct-password'),
    ]);
    $fieldUser->assignRole('Field User');

    foreach (['admin', 'org'] as $portal) {
        $response = $this->post('/login', [
            'email' => $fieldUser->email,
            'password' => 'correct-password',
            'portal' => $portal,
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
});

test('omitting the portal field defaults to the user portal', function () {
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
