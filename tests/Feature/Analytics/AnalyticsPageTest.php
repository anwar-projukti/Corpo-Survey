<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function orgAdminForAnalyticsPage(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

test('an Org Admin can view the analytics page for their survey', function () {
    [$organization, $admin] = orgAdminForAnalyticsPage();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/analytics");

    $response->assertOk()->assertSee($survey->title);
});

test('a Field User cannot access the analytics page', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($fieldUser)->get("/surveys/{$survey->id}/analytics");

    $response->assertForbidden();
});

test('the analytics page 404s for a survey in a different organization', function () {
    [, $admin] = orgAdminForAnalyticsPage();
    $otherOrg = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $otherOrg->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/analytics");

    $response->assertNotFound();
});
