<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;

test('a field user can view the my surveys page', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $response = $this->actingAs($fieldUser)->get('/my/surveys');

    $response->assertOk()->assertSee(__('surveys.field.my_surveys'));
});

test('a field user can view the form page for a survey assigned to them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $survey->setCurrentPublishedVersion($version);
    $survey->assignments()->create(['user_id' => $fieldUser->id]);

    $response = $this->actingAs($fieldUser)->get("/my/surveys/{$survey->id}");

    $response->assertOk()->assertSee($survey->title);
});

test('a field user cannot view the form page for a survey not assigned to them', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $survey->setCurrentPublishedVersion($version);

    $response = $this->actingAs($fieldUser)->get("/my/surveys/{$survey->id}");

    $response->assertForbidden();
});

test('an unauthenticated visitor is redirected away from the field portal', function () {
    $response = $this->get('/my/surveys');

    $response->assertRedirect('/login');
});

test('the field survey form page renders the assigned surveys question tree client side wiring', function () {
    $organization = Organization::factory()->create();
    $fieldUser = User::factory()->create(['organization_id' => $organization->id]);
    $fieldUser->assignRole('Field User');

    $survey = Survey::factory()->create(['organization_id' => $organization->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Name', 'order' => 0, 'is_required' => true]);
    $survey->setCurrentPublishedVersion($version);
    $survey->assignments()->create(['user_id' => $fieldUser->id]);

    $response = $this->actingAs($fieldUser)->get("/my/surveys/{$survey->id}");

    $response->assertOk()->assertSee("fieldSurveyForm({$survey->id})", escape: false);
});
