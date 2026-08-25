<?php

use App\Models\User;

test('a guest can switch locale and it applies to the very next request via session', function () {
    $this->post('/locale', ['locale' => 'bn']);

    $response = $this->get('/login');

    $response->assertOk();
    expect(app()->getLocale())->toBe('bn');
});

test('a logged in user switching locale persists it to their user record', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->post('/locale', ['locale' => 'bn']);

    expect($user->fresh()->locale)->toBe('bn');
});

test('a logged in users stored locale is applied even on a fresh session', function () {
    $user = User::factory()->create(['locale' => 'bn']);

    $this->actingAs($user)->get('/home');

    expect(app()->getLocale())->toBe('bn');
});

test('an unsupported locale is rejected', function () {
    $response = $this->post('/locale', ['locale' => 'fr']);

    $response->assertSessionHasErrors('locale');
});

test('the login page renders in Bangla when the locale is set to bn', function () {
    $this->post('/locale', ['locale' => 'bn']);

    $response = $this->get('/login');

    $response->assertSee('লগ ইন');
});

test('the dashboard sidebar renders translated navigation labels', function () {
    $user = User::factory()->create(['locale' => 'bn']);

    $response = $this->actingAs($user)->get('/home');

    $response->assertSee('ড্যাশবোর্ড');
});
