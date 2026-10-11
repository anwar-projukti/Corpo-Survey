<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Spec §5.7/§11 requires a general API rate-limit tier distinct from
 * public-survey/export tiers — this confirms the "api" limiter (spec
 * AppServiceProvider::boot()) is actually wired to the authenticated
 * route group, not just defined and unused.
 */
test('the general api rate limiter throttles an authenticated user past its ceiling', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    RateLimiter::for('api', fn () => Limit::perMinute(1)->by($user->id));

    $this->actingAs($user)->getJson('/api/v1/surveys')->assertOk();
    $response = $this->actingAs($user)->getJson('/api/v1/surveys');

    $response->assertStatus(429);
});
