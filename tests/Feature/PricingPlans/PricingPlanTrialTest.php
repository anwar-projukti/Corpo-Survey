<?php

use App\Models\Organization;
use App\Models\PricingPlan;
use App\Models\User;

test('registering through a plan with a trial stamps trial_ends_at on the new organization', function () {
    $plan = PricingPlan::create([
        'name_en' => 'Starter', 'name_bn' => 'স্টার্টার',
        'price_en' => '$10/mo', 'price_bn' => '৳১,০০০/মাস',
        'button_text_en' => 'Get started', 'button_text_bn' => 'শুরু করুন',
        'button_target' => 'register',
        'trial_days' => 30,
        'order' => 0,
    ]);

    $this->post('/register', [
        'plan' => $plan->id,
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $organization = Organization::where('name', 'Jane Research Co')->first();

    expect($organization->trial_ends_at)->not->toBeNull();
    $daysUntilTrialEnds = now()->diffInDays($organization->trial_ends_at);
    expect($daysUntilTrialEnds)->toBeGreaterThanOrEqual(29)
        ->and($daysUntilTrialEnds)->toBeLessThanOrEqual(30);
});

test('registering through a plan with no trial leaves trial_ends_at null', function () {
    $plan = PricingPlan::create([
        'name_en' => 'Enterprise', 'name_bn' => 'এন্টারপ্রাইজ',
        'price_en' => 'Contact us', 'price_bn' => 'যোগাযোগ করুন',
        'button_text_en' => 'Contact sales', 'button_text_bn' => 'যোগাযোগ করুন',
        'button_target' => 'contact',
        'trial_days' => null,
        'order' => 0,
    ]);

    $this->post('/register', [
        'plan' => $plan->id,
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $organization = Organization::where('name', 'Jane Research Co')->first();

    expect($organization->trial_ends_at)->toBeNull();
});

test('registering without a plan leaves trial_ends_at null', function () {
    $this->post('/register', [
        'name' => 'Jane Doe',
        'organization_name' => 'Jane Research Co',
        'email' => 'jane@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $organization = Organization::where('name', 'Jane Research Co')->first();

    expect($organization->trial_ends_at)->toBeNull();
});

test('a Super Admin can set a plans trial length', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    $plan = PricingPlan::create([
        'name_en' => 'Basic', 'name_bn' => 'বেসিক',
        'price_en' => '$29/mo', 'price_bn' => '৳২,৫০০/মাস',
        'button_text_en' => 'Get started', 'button_text_bn' => 'শুরু করুন',
        'button_target' => 'register',
        'order' => 0,
    ]);

    $response = $this->actingAs($superAdmin)->patchJson("/api/v1/pricing-plans/{$plan->id}", [
        'trial_days' => 30,
    ]);

    $response->assertOk();
    expect($response->json('trial_days'))->toBe(30);
    expect($plan->fresh()->hasTrial())->toBeTrue();
});

test('trial_days must be a non-negative integer within range', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    $response = $this->actingAs($superAdmin)->postJson('/api/v1/pricing-plans', [
        'name_en' => 'Basic', 'name_bn' => 'বেসিক',
        'price_en' => '$29/mo', 'price_bn' => '৳২,৫০০/মাস',
        'button_text_en' => 'Get started', 'button_text_bn' => 'শুরু করুন',
        'button_target' => 'register',
        'trial_days' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('trial_days');
});
