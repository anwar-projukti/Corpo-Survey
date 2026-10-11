<?php

use App\Models\Organization;
use App\Models\User;

function orgAdminForExportPages(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Org Admin');

    return $user;
}

test('the export history page renders for a user with response.export', function () {
    $user = orgAdminForExportPages();

    $response = $this->actingAs($user)->get('/exports');

    $response->assertOk()->assertSee(__('surveys.exports.title'));
});

test('a user without response.export cannot access the export history page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Field User');

    $response = $this->actingAs($user)->get('/exports');

    $response->assertForbidden();
});

test('the import wizard page renders for a user with response.import', function () {
    $user = orgAdminForExportPages();

    $response = $this->actingAs($user)->get('/respondents/import');

    $response->assertOk()->assertSee(__('surveys.imports.title'));
});

test('a user without response.import cannot access the import wizard page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Field User');

    $response = $this->actingAs($user)->get('/respondents/import');

    $response->assertForbidden();
});

test('the user productivity stats page renders for a user with report.view', function () {
    $user = orgAdminForExportPages();

    $response = $this->actingAs($user)->get('/reports/user-stats');

    $response->assertOk()->assertSee(__('surveys.user_stats.title'));
});

test('a user without report.view cannot access the user productivity stats page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Field User');

    $response = $this->actingAs($user)->get('/reports/user-stats');

    $response->assertForbidden();
});

test('a Super Admin gets a clean 404 instead of a crash on the export history page', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    $response = $this->actingAs($superAdmin)->get('/exports');

    $response->assertNotFound();
});

test('a Super Admin gets a clean 404 instead of a crash on the user productivity stats page', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $superAdmin->assignRole('Super Admin');

    $response = $this->actingAs($superAdmin)->get('/reports/user-stats');

    $response->assertNotFound();
});
