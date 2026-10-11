<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;

test('a brand new organization sees the onboarding checklist', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk()->assertSee(__('messages.onboarding.title'));
    $response->assertSee(__('surveys.builder.create_survey'));
});

test('the create survey step no longer shows its call-to-action once a survey exists', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');
    Survey::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($admin)->get('/home');

    // home.blade.php only renders the "Create your first survey ->" CTA
    // link (@unless($checklist['has_survey'])) while that step is
    // outstanding — once a survey exists it disappears even though the
    // step's own label text is still shown, struck through.
    $response->assertOk()->assertDontSee(__('surveys.builder.create_survey').' →');
});

test('an organization with a real response sees the ordinary dashboard instead of onboarding', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole('Org Admin');
    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    SurveyResponse::factory()->create([
        'organization_id' => $organization->id,
        'survey_id' => $survey->id,
        'survey_version_id' => $version->id,
    ]);

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk()
        ->assertDontSee(__('messages.onboarding.title'))
        ->assertSee(__('messages.onboarding.stat_responses'));
});

test('the onboarding checklist only counts the viewing organizations own data', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();
    $admin = User::factory()->create(['organization_id' => $organizationA->id]);
    $admin->assignRole('Org Admin');
    Survey::factory()->create(['organization_id' => $organizationB->id]);

    $response = $this->actingAs($admin)->get('/home');

    $response->assertOk()->assertSee(__('messages.onboarding.title'));
});
