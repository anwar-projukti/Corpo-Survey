<?php

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\QuestionOption;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveySection;
use App\Models\SurveyVersion;
use App\Models\User;

test('a survey can have a dynamic number of questions with no schema change', function () {
    $version = SurveyVersion::factory()->create();
    $section = $version->sections()->create(['title' => 'Section A', 'order' => 0]);

    foreach (range(1, 120) as $i) {
        $section->questions()->create([
            'type' => QuestionType::ShortText,
            'label' => "Question $i",
            'order' => $i,
        ]);
    }

    expect($section->questions()->count())->toBe(120);
});

test('a question of any type stores arbitrary config as JSON', function () {
    $question = SurveyQuestion::factory()->create([
        'type' => QuestionType::Matrix,
        'config' => [
            'matrixRows' => ['Speed', 'Quality', 'Support'],
            'matrixColumns' => ['Poor', 'OK', 'Great'],
        ],
    ]);

    $fresh = $question->fresh();

    expect($fresh->config['matrixRows'])->toBe(['Speed', 'Quality', 'Support'])
        ->and($fresh->type)->toBe(QuestionType::Matrix);
});

test('a single choice question has ordered options', function () {
    $question = SurveyQuestion::factory()->singleChoice()->create();
    $question->options()->create(['label' => 'B', 'value' => 'b', 'order' => 1]);
    $question->options()->create(['label' => 'A', 'value' => 'a', 'order' => 0]);

    expect($question->options->pluck('label')->all())->toBe(['A', 'B']);
});

test('a survey version tracks its own status independently of the survey', function () {
    $survey = Survey::factory()->create();
    $published = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id, 'version_number' => 1]);
    $draft = SurveyVersion::factory()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    $survey->setCurrentPublishedVersion($published);
    $survey->setCurrentDraftVersion($draft);

    expect($survey->fresh()->isPublished())->toBeTrue()
        ->and($survey->currentPublishedVersion->isPublished())->toBeTrue()
        ->and($survey->currentDraftVersion->isDraft())->toBeTrue();
});

test('setCurrentDraftAndPublishedVersions sets both pointers in one call', function () {
    $survey = Survey::factory()->create();
    $published = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id, 'version_number' => 1]);
    $draft = SurveyVersion::factory()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    $survey->setCurrentDraftAndPublishedVersions($draft, $published);

    $fresh = $survey->fresh();
    expect($fresh->current_draft_version_id)->toBe($draft->id)
        ->and($fresh->current_published_version_id)->toBe($published->id);
});

test('current version pointers are not mass-assignable', function () {
    $survey = Survey::factory()->create();
    $version = SurveyVersion::factory()->create(['survey_id' => $survey->id]);

    // A raw update()/create() must never be able to flip which version is
    // "live" — only Survey::setCurrentPublishedVersion()/setCurrentDraftVersion()
    // (a controlled publish workflow) may do that.
    $survey->update(['current_published_version_id' => $version->id]);

    expect($survey->fresh()->current_published_version_id)->toBeNull();
});

test('historical responses would stay tied to their original version (version numbers are stable)', function () {
    $survey = Survey::factory()->create();
    $v1 = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id, 'version_number' => 1]);
    $v2 = SurveyVersion::factory()->create(['survey_id' => $survey->id, 'version_number' => 2]);

    expect($v1->id)->not->toBe($v2->id)
        ->and($survey->versions()->count())->toBe(2);
});

test('deleting a survey cascades to its versions, sections, questions, and options', function () {
    $survey = Survey::factory()->create();
    $version = SurveyVersion::factory()->create(['survey_id' => $survey->id]);
    $section = $version->sections()->create(['title' => 'S', 'order' => 0]);
    $question = $section->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Q', 'order' => 0]);
    $question->options()->create(['label' => 'O', 'value' => 'o', 'order' => 0]);

    $survey->forceDelete();

    expect(SurveyVersion::find($version->id))->toBeNull()
        ->and(SurveySection::find($section->id))->toBeNull()
        ->and(SurveyQuestion::find($question->id))->toBeNull()
        ->and(QuestionOption::count())->toBe(0);
});

test('a survey is scoped to its organization and invisible to other organizations', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    $surveyA = Survey::factory()->create(['organization_id' => $orgA->id, 'created_by' => $userA->id]);
    Survey::factory()->create(['organization_id' => $orgB->id, 'created_by' => $userB->id]);

    $this->actingAs($userA);

    $visible = Survey::pluck('id');

    expect($visible)->toContain($surveyA->id)
        ->and($visible)->toHaveCount(1);
});

test('a new survey stamps organization_id from the authenticated user', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user);

    $survey = Survey::create([
        'created_by' => $user->id,
        'title' => 'Test Survey',
        'slug' => 'test-survey',
    ]);

    expect($survey->organization_id)->toBe($organization->id);
});

test('organization_id in the input array cannot override the authenticated users organization', function () {
    $ownOrganization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $ownOrganization->id]);

    $this->actingAs($user);

    // Simulates a client sending a foreign organization_id in the request
    // body — Survey::$fillable must not include it, or this would attach
    // the survey to a tenant the user doesn't belong to.
    $survey = Survey::create([
        'organization_id' => $otherOrganization->id,
        'created_by' => $user->id,
        'title' => 'Test Survey',
        'slug' => 'test-survey',
    ]);

    expect($survey->organization_id)->toBe($ownOrganization->id)
        ->and($survey->organization_id)->not->toBe($otherOrganization->id);
});
