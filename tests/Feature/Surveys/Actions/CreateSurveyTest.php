<?php

use App\Actions\Surveys\CreateSurvey;
use App\Enums\SurveyVersionStatus;
use App\Models\Organization;
use App\Models\User;

test('creates a survey with an initial draft version', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $survey = (new CreateSurvey)->handle($user, ['title' => 'My Survey']);

    expect($survey->title)->toBe('My Survey')
        ->and($survey->slug)->toBe('my-survey')
        ->and($survey->organization_id)->toBe($organization->id)
        ->and($survey->created_by)->toBe($user->id)
        ->and($survey->currentDraftVersion->status)->toBe(SurveyVersionStatus::Draft)
        ->and($survey->currentDraftVersion->version_number)->toBe(1)
        ->and($survey->currentPublishedVersion)->toBeNull();
});

test('two surveys with the same title in the same organization get distinct slugs', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $first = (new CreateSurvey)->handle($user, ['title' => 'Customer Feedback']);
    $second = (new CreateSurvey)->handle($user, ['title' => 'Customer Feedback']);

    expect($first->slug)->not->toBe($second->slug);
});

test('the same title is allowed to reuse a slug in a different organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    $surveyA = (new CreateSurvey)->handle($userA, ['title' => 'Customer Feedback']);
    $surveyB = (new CreateSurvey)->handle($userB, ['title' => 'Customer Feedback']);

    expect($surveyA->slug)->toBe($surveyB->slug);
});
