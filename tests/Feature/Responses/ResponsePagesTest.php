<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;

function orgAdminForResponsePages(): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    return [$organization, $admin];
}

test('an Org Admin can view the response table page for their survey', function () {
    [$organization, $admin] = orgAdminForResponsePages();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/responses");

    $response->assertOk()->assertSee($survey->title);
});

test('a user without survey.view cannot access the response table page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($user)->get("/surveys/{$survey->id}/responses");

    $response->assertForbidden();
});

test('an Org Admin can view a response detail page', function () {
    [$organization, $admin] = orgAdminForResponsePages();
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $survey->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/responses/{$surveyResponse->id}");

    $response->assertOk()->assertSee("responseDetail({$survey->id}, {$surveyResponse->id})", escape: false);
});

test('a response detail page 404s when the response does not belong to the survey in the url', function () {
    [$organization, $admin] = orgAdminForResponsePages();
    $surveyA = Survey::factory()->create(['organization_id' => $organization->id]);
    $surveyB = Survey::factory()->create(['organization_id' => $organization->id]);
    $responseForB = SurveyResponse::factory()->create(['organization_id' => $organization->id, 'survey_id' => $surveyB->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$surveyA->id}/responses/{$responseForB->id}");

    $response->assertNotFound();
});

test('a response detail page 404s for a survey in a different organization', function () {
    // Survey's BelongsToOrganization global scope filters the row out of
    // the query entirely before route-model binding resolves it, so this
    // 404s rather than 403s — the same behavior SurveyPolicyTest documents
    // for every other survey endpoint under normal (scoped) route-model
    // binding.
    [, $admin] = orgAdminForResponsePages();
    $otherOrg = Organization::factory()->create();
    $survey = Survey::factory()->create(['organization_id' => $otherOrg->id]);
    $surveyResponse = SurveyResponse::factory()->create(['organization_id' => $otherOrg->id, 'survey_id' => $survey->id]);

    $response = $this->actingAs($admin)->get("/surveys/{$survey->id}/responses/{$surveyResponse->id}");

    $response->assertNotFound();
});
