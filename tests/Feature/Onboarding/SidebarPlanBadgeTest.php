<?php

use App\Models\Organization;
use App\Models\PricingPlan;
use App\Models\User;

test('registering through a plan links the organization to it', function () {
    $plan = PricingPlan::create([
        'name_en' => 'Basic', 'name_bn' => 'বেসিক',
        'price_en' => '$29/mo', 'price_bn' => '৳২,৫০০/মাস',
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

    expect($organization->pricing_plan_id)->toBe($plan->id)
        ->and($organization->pricingPlan->name_en)->toBe('Basic');
});

test('the sidebar shows the organizations plan name and days left while on trial', function () {
    $plan = PricingPlan::create([
        'name_en' => 'Basic', 'name_bn' => 'বেসিক',
        'price_en' => '$29/mo', 'price_bn' => '৳২,৫০০/মাস',
        'button_text_en' => 'Get started', 'button_text_bn' => 'শুরু করুন',
        'button_target' => 'register',
        'order' => 0,
    ]);
    $organization = Organization::factory()->create([
        'pricing_plan_id' => $plan->id,
        'trial_ends_at' => now()->addDays(30),
    ]);
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk();
    $response->assertSee('Basic');
    $response->assertSee('30 days left');
});

test('the sidebar shows a trial expired badge once the trial has passed', function () {
    $organization = Organization::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk();
    $response->assertSee('Trial expired');
});

test('the sidebar shows no plan or trial badge for an organization without either', function () {
    $organization = Organization::factory()->create(['pricing_plan_id' => null, 'trial_ends_at' => null]);
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk();
    $response->assertDontSee('days left');
    $response->assertDontSee('Trial expired');
});
