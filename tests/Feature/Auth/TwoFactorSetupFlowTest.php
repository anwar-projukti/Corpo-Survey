<?php

use App\Models\User;
use Illuminate\Support\Facades\Date;
use PragmaRX\Google2FA\Google2FA;

/**
 * Exercises Fortify's own two-factor endpoints (routes/web.php never
 * defines these — see vendor/laravel/fortify/routes/routes.php) the way
 * resources/js/two-factor-setup.js actually calls them, proving the
 * enable -> confirm -> recovery-codes flow genuinely works end to end
 * for the app's real "Org Admin must have 2FA" requirement (spec §5.1).
 */
function withConfirmedPassword(User $user)
{
    return test()->actingAs($user)->withSession([
        'auth.password_confirmed_at' => Date::now()->unix(),
    ]);
}

test('enabling two factor authentication generates a secret but does not confirm it yet', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    withConfirmedPassword($orgAdmin)->postJson('/user/two-factor-authentication')->assertStatus(200);

    expect($orgAdmin->fresh()->two_factor_secret)->not->toBeNull()
        ->and($orgAdmin->fresh()->two_factor_confirmed_at)->toBeNull()
        ->and($orgAdmin->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('the qr code endpoint returns an svg and manual setup url once enabled', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');
    $session = withConfirmedPassword($orgAdmin);

    $session->postJson('/user/two-factor-authentication')->assertOk();

    $response = $session->getJson('/user/two-factor-qr-code');

    $response->assertOk()
        ->assertJsonStructure(['svg', 'url']);
    expect($response->json('svg'))->toContain('<svg');
});

test('confirming with the correct current code marks two factor authentication as fully enabled', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');
    $session = withConfirmedPassword($orgAdmin);

    $session->postJson('/user/two-factor-authentication')->assertOk();

    $secret = decrypt($orgAdmin->fresh()->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $session->postJson('/user/confirmed-two-factor-authentication', ['code' => $code]);

    $response->assertStatus(200);
    expect($orgAdmin->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

test('confirming with an incorrect code fails and leaves two factor authentication unconfirmed', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');
    $session = withConfirmedPassword($orgAdmin);

    $session->postJson('/user/two-factor-authentication')->assertOk();

    $response = $session->postJson('/user/confirmed-two-factor-authentication', ['code' => '000000']);

    $response->assertStatus(422);
    expect($orgAdmin->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('once confirmed, the recovery codes endpoint returns codes and the Org Admin reaches the dashboard', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');
    $session = withConfirmedPassword($orgAdmin);

    $session->postJson('/user/two-factor-authentication')->assertOk();

    $secret = decrypt($orgAdmin->fresh()->two_factor_secret);
    $code = app(Google2FA::class)->getCurrentOtp($secret);
    $session->postJson('/user/confirmed-two-factor-authentication', ['code' => $code])->assertStatus(200);

    $recoveryCodes = $session->getJson('/user/two-factor-recovery-codes');
    $recoveryCodes->assertOk();
    expect($recoveryCodes->json())->toBeArray()->not->toBeEmpty();

    // hasEnabledTwoFactorAuthentication() is now true, so
    // RequireTwoFactorForPrivilegedRoles lets this same Org Admin straight
    // through to the dashboard on their very next request.
    $dashboard = $this->actingAs($orgAdmin)->get('/home');
    $dashboard->assertOk();
});

test('enabling two factor authentication without a freshly confirmed password is rejected', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole('Org Admin');

    $response = $this->actingAs($orgAdmin)->postJson('/user/two-factor-authentication');

    $response->assertStatus(423);
});
