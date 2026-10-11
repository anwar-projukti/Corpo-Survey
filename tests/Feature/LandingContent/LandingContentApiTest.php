<?php

use App\Models\LandingContent;
use App\Models\User;

function confirmedSuperAdminForLandingContentApi(): User
{
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    return $superAdmin;
}

test('a Super Admin can view the landing page content', function () {
    $superAdmin = confirmedSuperAdminForLandingContentApi();

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/landing-content');

    $response->assertOk();
    expect($response->json('hero_tagline_en'))->toBeNull();
});

test('a Super Admin can update the landing page content', function () {
    $superAdmin = confirmedSuperAdminForLandingContentApi();

    $response = $this->actingAs($superAdmin)->putJson('/api/v1/landing-content', [
        'hero_tagline_en' => 'Custom headline',
        'contact_email' => 'contact@example.com',
        'contact_phone' => '+880 1700-000000',
    ]);

    $response->assertOk();
    expect($response->json('hero_tagline_en'))->toBe('Custom headline');

    $landingContent = LandingContent::current();
    expect($landingContent->hero_tagline_en)->toBe('Custom headline')
        ->and($landingContent->contact_email)->toBe('contact@example.com')
        ->and($landingContent->updated_by)->toBe($superAdmin->id);
});

test('an Org Admin cannot view or update the landing page content', function () {
    $orgAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $orgAdmin->assignRole('Org Admin');

    $this->actingAs($orgAdmin)->getJson('/api/v1/landing-content')->assertForbidden();
    $this->actingAs($orgAdmin)->putJson('/api/v1/landing-content', ['hero_tagline_en' => 'Hijacked'])->assertForbidden();
});

test('an unedited field falls back to the lang file string', function () {
    $landingContent = LandingContent::current();

    expect($landingContent->text('hero_tagline', 'en'))->toBe(__('messages.landing.tagline'))
        ->and($landingContent->text('hero_tagline', 'bn'))->toBe(__('messages.landing.tagline', [], 'bn'));
});

test('editing one locale does not affect the other', function () {
    $landingContent = LandingContent::current();
    $landingContent->update(['hero_tagline_en' => 'English only edit']);
    $landingContent->refresh();

    expect($landingContent->text('hero_tagline', 'en'))->toBe('English only edit')
        ->and($landingContent->text('hero_tagline', 'bn'))->toBe(__('messages.landing.tagline', [], 'bn'));
});

test('the public landing page renders customized content', function () {
    $landingContent = LandingContent::current();
    $landingContent->update(['hero_tagline_en' => 'Rendered on the homepage']);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Rendered on the homepage');
});
