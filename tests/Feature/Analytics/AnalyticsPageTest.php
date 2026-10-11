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

test('an Org Admin sees the saved report New/Edit/Delete controls', function () {
    [$organization, $admin] = orgAdminForAnalyticsPage();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/analytics");

    $response->assertOk()
        ->assertSee(__('surveys.analytics.reports_new'))
        ->assertSee(__('surveys.analytics.reports_edit'))
        ->assertSee(__('surveys.analytics.reports_delete'));
});

test('a Viewer also holds report.export, so still sees the saved report write controls', function () {
    $organization = Organization::factory()->create();
    $viewer = User::factory()->create(['organization_id' => $organization->id]);
    $viewer->assignRole('Viewer');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($viewer)->get("/surveys/{$survey->id}/analytics");

    $response->assertOk()
        ->assertSee(__('surveys.analytics.reports_new'))
        ->assertSee(__('surveys.analytics.reports_edit'))
        ->assertSee(__('surveys.analytics.reports_delete'));
});

test('an Editor has report.view but not report.export, so cannot see the saved report write controls', function () {
    $organization = Organization::factory()->create();
    $editor = User::factory()->create(['organization_id' => $organization->id]);
    $editor->assignRole('Editor');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($editor)->get("/surveys/{$survey->id}/analytics");

    $response->assertOk()->assertDontSee(__('surveys.analytics.reports_new'));
});
