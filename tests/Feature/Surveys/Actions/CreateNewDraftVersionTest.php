<?php

use App\Actions\Surveys\CreateNewDraftVersion;
use App\Enums\QuestionType;
use App\Enums\RuleAction;
use App\Enums\SurveyVersionStatus;
use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function publishedSurveyWithRule(Organization $organization, User $creator): Survey
{
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $creator->id]);
    $version = SurveyVersion::factory()->published()->create(['survey_id' => $survey->id, 'version_number' => 1]);

    $section = $version->sections()->create(['title' => 'About You', 'order' => 0]);
    $ageQuestion = $section->questions()->create([
        'type' => QuestionType::Number, 'label' => 'Age', 'order' => 0, 'config' => ['min' => 0],
    ]);
    $choiceQuestion = $section->questions()->create([
        'type' => QuestionType::SingleChoice, 'label' => 'Recommend?', 'order' => 1,
    ]);
    $choiceQuestion->options()->create(['label' => 'Yes', 'value' => 'yes', 'order' => 0]);
    $noOption = $choiceQuestion->options()->create(['label' => 'No', 'value' => 'no', 'order' => 1]);

    $followUp = $section->questions()->create(['type' => QuestionType::LongText, 'label' => 'Why not?', 'order' => 2]);

    $version->rules()->create([
        'name' => 'Show follow-up',
        'conditions' => ['question_id' => $choiceQuestion->id, 'operator' => 'equals', 'value' => $noOption->value],
        'action' => RuleAction::Show,
        'target' => ['question_id' => $followUp->id],
        'order' => 0,
    ]);

    $survey->setCurrentPublishedVersion($version);

    return $survey->fresh();
}

test('clones the full section/question/option tree into a new draft', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = publishedSurveyWithRule($organization, $creator);

    $draft = (new CreateNewDraftVersion)->handle($survey);

    expect($draft->status)->toBe(SurveyVersionStatus::Draft)
        ->and($draft->version_number)->toBe(2)
        ->and($draft->sections)->toHaveCount(1)
        ->and($draft->sections->first()->questions)->toHaveCount(3)
        ->and($draft->sections->first()->questions->firstWhere('type', QuestionType::SingleChoice)->options)->toHaveCount(2);
});

test('cloned questions have new ids distinct from the source version', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = publishedSurveyWithRule($organization, $creator);
    $sourceQuestionIds = $survey->currentPublishedVersion->sections->flatMap->questions->pluck('id');

    $draft = (new CreateNewDraftVersion)->handle($survey);
    $draftQuestionIds = $draft->sections->flatMap->questions->pluck('id');

    expect($sourceQuestionIds->intersect($draftQuestionIds))->toBeEmpty();
});

test('a rule referencing questions is cloned with remapped ids that still resolve', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = publishedSurveyWithRule($organization, $creator);

    $draft = (new CreateNewDraftVersion)->handle($survey);

    expect($draft->rules)->toHaveCount(1);

    $clonedRule = $draft->rules->first();
    $choiceQuestion = $draft->sections->first()->questions->firstWhere('type', QuestionType::SingleChoice);
    $followUpQuestion = $draft->sections->first()->questions->firstWhere('label', 'Why not?');

    expect($clonedRule->conditions['question_id'])->toBe($choiceQuestion->id)
        ->and($clonedRule->target['question_id'])->toBe($followUpQuestion->id)
        ->and($clonedRule->conditions['value'])->toBe('no');
});

test('the new draft becomes the surveys current draft version', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = publishedSurveyWithRule($organization, $creator);

    $draft = (new CreateNewDraftVersion)->handle($survey);

    expect($survey->fresh()->current_draft_version_id)->toBe($draft->id)
        ->and($survey->fresh()->current_published_version_id)->not->toBeNull();
});

test('creating a new draft fails if one already exists', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = publishedSurveyWithRule($organization, $creator);
    (new CreateNewDraftVersion)->handle($survey);

    (new CreateNewDraftVersion)->handle($survey->fresh());
})->throws(ValidationException::class);

test('creating a new draft fails if there is no published version', function () {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create(['organization_id' => $organization->id]);
    $survey = Survey::factory()->create(['organization_id' => $organization->id, 'created_by' => $creator->id]);

    (new CreateNewDraftVersion)->handle($survey);
})->throws(ValidationException::class);
