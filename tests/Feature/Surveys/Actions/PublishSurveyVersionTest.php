<?php

use App\Actions\Surveys\PublishSurveyVersion;
use App\Enums\QuestionType;
use App\Enums\SurveyVersionStatus;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function surveyWithOneQuestion(Organization $organization, User $creator): Survey
{
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $creator->id]);
    $version = SurveyVersion::factory()->create(['survey_id' => $survey->id, 'version_number' => 1]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);
    $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentDraftVersion($version);

    return $survey->fresh();
}

test('publishing a draft makes it the current published version', function () {
    $organization = Organization::factory()->create();
    $publisher = User::factory()->create(['organization_id' => $organization->id]);
    $survey = surveyWithOneQuestion($organization, $publisher);
    $draftId = $survey->currentDraftVersion->id;

    $survey = (new PublishSurveyVersion)->handle($survey, $publisher);

    expect($survey->current_published_version_id)->toBe($draftId)
        ->and($survey->current_draft_version_id)->toBeNull()
        ->and($survey->currentPublishedVersion->status)->toBe(SurveyVersionStatus::Published)
        ->and($survey->currentPublishedVersion->published_by)->toBe($publisher->id)
        ->and($survey->currentPublishedVersion->published_at)->not->toBeNull();
});

test('publishing archives the previously published version', function () {
    $organization = Organization::factory()->create();
    $publisher = User::factory()->create(['organization_id' => $organization->id]);
    $survey = surveyWithOneQuestion($organization, $publisher);

    $survey = (new PublishSurveyVersion)->handle($survey, $publisher);
    $previouslyPublishedId = $survey->current_published_version_id;

    $newDraft = $survey->versions()->create([
        'version_number' => 2,
        'status' => SurveyVersionStatus::Draft,
    ]);
    $newDraft->sections()->create(['title' => 'S', 'order' => 0])
        ->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $survey->setCurrentDraftVersion($newDraft);

    $survey = (new PublishSurveyVersion)->handle($survey->fresh(), $publisher);

    expect($survey->current_published_version_id)->toBe($newDraft->id)
        ->and(SurveyVersion::find($previouslyPublishedId)->status)->toBe(SurveyVersionStatus::Archived);
});

test('publishing a survey with no draft version fails', function () {
    $organization = Organization::factory()->create();
    $publisher = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $publisher->id]);

    (new PublishSurveyVersion)->handle($survey, $publisher);
})->throws(ValidationException::class);

test('publishing a survey with no questions fails', function () {
    $organization = Organization::factory()->create();
    $publisher = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $publisher->id]);
    $version = SurveyVersion::factory()->create(['survey_id' => $survey->id]);
    $version->sections()->create(['title' => 'Empty section', 'order' => 0]);
    $survey->setCurrentDraftVersion($version);

    (new PublishSurveyVersion)->handle($survey->fresh(), $publisher);
})->throws(ValidationException::class);
