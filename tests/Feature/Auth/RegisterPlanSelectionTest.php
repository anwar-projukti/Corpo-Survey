<?php

use App\Models\PricingPlan;

test('the register page shows the selected plans name and price when linked from pricing', function () {
    $plan = PricingPlan::first();

    $response = $this->get('/register?plan='.$plan->id);

    $response->assertOk();
    $response->assertSee($plan->name_en);
    $response->assertSee($plan->price_en);
});

test('the register page shows no plan banner without a plan parameter', function () {
    $response = $this->get('/register');

    $response->assertOk();
    $response->assertDontSee(__('messages.auth.selected_plan'));
});

test('an invalid plan id is ignored rather than erroring', function () {
    $response = $this->get('/register?plan=999999');

    $response->assertOk();
    $response->assertDontSee(__('messages.auth.selected_plan'));
});
