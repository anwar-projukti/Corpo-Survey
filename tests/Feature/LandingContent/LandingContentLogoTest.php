<?php

use App\Models\LandingContent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function confirmedSuperAdminForLogoTest(): User
{
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    return $superAdmin;
}

test('a Super Admin can upload a custom logo', function () {
    Storage::fake('public');
    $superAdmin = confirmedSuperAdminForLogoTest();

    $file = UploadedFile::fake()->image('logo.png', 128, 128);

    $response = $this->actingAs($superAdmin)->post('/api/v1/landing-content/logo', [
        'logo' => $file,
    ]);

    $response->assertOk();
    $landingContent = LandingContent::current();
    expect($landingContent->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($landingContent->logo_path);
    expect($response->json('logo_url'))->toContain('storage');
});

test('uploading a new logo removes the previous one', function () {
    Storage::fake('public');
    $superAdmin = confirmedSuperAdminForLogoTest();

    $first = UploadedFile::fake()->image('first.png', 128, 128);
    $this->actingAs($superAdmin)->post('/api/v1/landing-content/logo', ['logo' => $first])->assertOk();
    $firstPath = LandingContent::current()->fresh()->logo_path;

    $second = UploadedFile::fake()->image('second.png', 128, 128);
    $this->actingAs($superAdmin)->post('/api/v1/landing-content/logo', ['logo' => $second])->assertOk();

    Storage::disk('public')->assertMissing($firstPath);
});

test('a Super Admin can remove the custom logo back to the default', function () {
    Storage::fake('public');
    $superAdmin = confirmedSuperAdminForLogoTest();

    $file = UploadedFile::fake()->image('logo.png', 128, 128);
    $this->actingAs($superAdmin)->post('/api/v1/landing-content/logo', ['logo' => $file])->assertOk();

    $response = $this->actingAs($superAdmin)->delete('/api/v1/landing-content/logo');
    $response->assertNoContent();

    $landingContent = LandingContent::current()->fresh();
    expect($landingContent->logo_path)->toBeNull();
    expect($landingContent->logoUrl())->toEndWith('logo-icon.png');
});

test('a non-square, too-small, or oversized logo is rejected', function () {
    Storage::fake('public');
    $superAdmin = confirmedSuperAdminForLogoTest();

    $tooSmall = UploadedFile::fake()->image('tiny.png', 32, 32);
    $this->actingAs($superAdmin)
        ->post('/api/v1/landing-content/logo', ['logo' => $tooSmall], ['Accept' => 'application/json'])
        ->assertStatus(422);

    $notAnImage = UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf');
    $this->actingAs($superAdmin)
        ->post('/api/v1/landing-content/logo', ['logo' => $notAnImage], ['Accept' => 'application/json'])
        ->assertStatus(422);
});

test('an Org Admin cannot upload a logo', function () {
    Storage::fake('public');
    $orgAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $orgAdmin->assignRole('Org Admin');

    $file = UploadedFile::fake()->image('logo.png', 128, 128);

    $this->actingAs($orgAdmin)->post('/api/v1/landing-content/logo', ['logo' => $file])
        ->assertForbidden();
});
