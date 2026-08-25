<?php

use App\Models\User;

test('the layout renders dir="ltr" for the current supported locales', function () {
    $user = User::factory()->create(['locale' => 'bn']);

    $response = $this->actingAs($user)->get('/home');

    $response->assertSee('dir="ltr"', escape: false);
});

test('the layout would render dir="rtl" if a locale were added to the rtl list', function () {
    config(['app.rtl_locales' => ['en']]);
    $user = User::factory()->create(['locale' => 'en']);

    $response = $this->actingAs($user)->get('/home');

    $response->assertSee('dir="rtl"', escape: false);
});
