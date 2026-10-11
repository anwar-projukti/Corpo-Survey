<?php

use App\Models\PricingPlan;
use App\Models\User;

function confirmedSuperAdminForPricingPlanApi(): User
{
    $superAdmin = User::factory()->superAdmin()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $superAdmin->assignRole('Super Admin');

    return $superAdmin;
}

function pricingPlanPayload(array $overrides = []): array
{
    return array_merge([
        'name_en' => 'Pro',
        'name_bn' => 'প্রো',
        'price_en' => '$49/mo',
        'price_bn' => '৳৪,০০০/মাস',
        'description_en' => 'For growing teams.',
        'description_bn' => 'বর্ধনশীল দলের জন্য।',
        'features_en' => ['10 surveys', '2000 responses/mo'],
        'features_bn' => ['১০টি জরিপ', 'মাসে ২০০০টি রেসপন্স'],
        'button_text_en' => 'Get started',
        'button_text_bn' => 'শুরু করুন',
        'button_target' => 'register',
        'highlighted' => false,
    ], $overrides);
}

test('a Super Admin can list pricing plans ordered by their sort order', function () {
    PricingPlan::query()->delete();
    $superAdmin = confirmedSuperAdminForPricingPlanApi();
    $second = PricingPlan::create([...pricingPlanPayload(['name_en' => 'Second']), 'order' => 1]);
    $first = PricingPlan::create([...pricingPlanPayload(['name_en' => 'First']), 'order' => 0]);

    $response = $this->actingAs($superAdmin)->getJson('/api/v1/pricing-plans');

    $response->assertOk();
    expect($response->json())->toHaveCount(2);
    expect($response->json('0.name_en'))->toBe('First');
    expect($response->json('1.name_en'))->toBe('Second');
});

test('a Super Admin can create a pricing plan', function () {
    $superAdmin = confirmedSuperAdminForPricingPlanApi();

    $response = $this->actingAs($superAdmin)->postJson('/api/v1/pricing-plans', pricingPlanPayload());

    $response->assertCreated();
    $this->assertDatabaseHas('pricing_plans', ['name_en' => 'Pro', 'updated_by' => $superAdmin->id]);
    expect($response->json('features_en'))->toBe(['10 surveys', '2000 responses/mo']);
});

test('a Super Admin can update a pricing plan', function () {
    $superAdmin = confirmedSuperAdminForPricingPlanApi();
    $plan = PricingPlan::create(pricingPlanPayload());

    $response = $this->actingAs($superAdmin)->patchJson("/api/v1/pricing-plans/{$plan->id}", [
        'price_en' => '$59/mo',
        'highlighted' => true,
    ]);

    $response->assertOk();
    expect($response->json('price_en'))->toBe('$59/mo')
        ->and($response->json('highlighted'))->toBeTrue()
        ->and($response->json('name_en'))->toBe('Pro');
});

test('a Super Admin can delete a pricing plan', function () {
    $superAdmin = confirmedSuperAdminForPricingPlanApi();
    $plan = PricingPlan::create(pricingPlanPayload());

    $this->actingAs($superAdmin)->deleteJson("/api/v1/pricing-plans/{$plan->id}")->assertNoContent();

    $this->assertDatabaseMissing('pricing_plans', ['id' => $plan->id]);
});

test('a Super Admin can reorder pricing plans', function () {
    PricingPlan::query()->delete();
    $superAdmin = confirmedSuperAdminForPricingPlanApi();
    $first = PricingPlan::create([...pricingPlanPayload(['name_en' => 'A']), 'order' => 0]);
    $second = PricingPlan::create([...pricingPlanPayload(['name_en' => 'B']), 'order' => 1]);

    $this->actingAs($superAdmin)
        ->postJson('/api/v1/pricing-plans/reorder', ['order' => [$second->id, $first->id]])
        ->assertNoContent();

    expect($first->fresh()->order)->toBe(1)
        ->and($second->fresh()->order)->toBe(0);
});

test('reordering with a mismatched set of ids is rejected', function () {
    $superAdmin = confirmedSuperAdminForPricingPlanApi();
    $plan = PricingPlan::create(pricingPlanPayload());

    $this->actingAs($superAdmin)
        ->postJson('/api/v1/pricing-plans/reorder', ['order' => [$plan->id, 99999]])
        ->assertStatus(422);
});

test('an Org Admin cannot manage pricing plans', function () {
    $orgAdmin = User::factory()->create([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $orgAdmin->assignRole('Org Admin');
    $plan = PricingPlan::create(pricingPlanPayload());

    $this->actingAs($orgAdmin)->getJson('/api/v1/pricing-plans')->assertForbidden();
    $this->actingAs($orgAdmin)->postJson('/api/v1/pricing-plans', pricingPlanPayload())->assertForbidden();
    $this->actingAs($orgAdmin)->patchJson("/api/v1/pricing-plans/{$plan->id}", ['price_en' => 'Hijacked'])->assertForbidden();
    $this->actingAs($orgAdmin)->deleteJson("/api/v1/pricing-plans/{$plan->id}")->assertForbidden();
});

test('the public landing page renders every pricing plan in order', function () {
    PricingPlan::query()->delete();
    PricingPlan::create([...pricingPlanPayload(['name_en' => 'Basic Plan Name']), 'order' => 0]);
    PricingPlan::create([...pricingPlanPayload(['name_en' => 'Enterprise Plan Name', 'highlighted' => true]), 'order' => 1]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSeeInOrder(['Basic Plan Name', 'Enterprise Plan Name']);
    $response->assertSee('Most popular');
});
