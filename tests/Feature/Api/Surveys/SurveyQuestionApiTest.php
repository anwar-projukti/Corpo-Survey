<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveySection;
use App\Models\User;

function draftSectionFor(User $user): SurveySection
{
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($version);

    return $version->sections()->create(['title' => 'S', 'order' => 0]);
}

function editorForQuestions(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('a short text question can be created with no config', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'short_text',
        'label' => 'What is your name?',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'short_text')
        ->assertJsonPath('data.label', 'What is your name?');
});

test('an unknown question type is rejected', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'not_a_real_type',
        'label' => 'X',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('type');
});

test('a matrix question requires matrixRows and matrixColumns in config', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'matrix',
        'label' => 'Rate these',
        'config' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['config.matrixRows', 'config.matrixColumns']);
});

test('a matrix question with valid config succeeds', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'matrix',
        'label' => 'Rate these',
        'config' => [
            'matrixRows' => ['Speed', 'Quality'],
            'matrixColumns' => ['Poor', 'Great'],
        ],
    ]);

    $response->assertCreated();
});

test('an empty-string matrix row is rejected (ConvertEmptyStringsToNull turns it into null first)', function () {
    // The builder's "Add question" flow originally defaulted a fresh
    // matrix question's rows/columns to [''], which Laravel's own
    // ConvertEmptyStringsToNull middleware converts to [null] before this
    // validation ever runs — failing the 'string' rule. Locks in why the
    // JS-side default must be a non-empty placeholder instead.
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'matrix',
        'label' => 'Rate these',
        'config' => [
            'matrixRows' => [''],
            'matrixColumns' => ['Column'],
        ],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('config.matrixRows.0');
});

test('a number question rejects max less than min', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'number',
        'label' => 'Age',
        'config' => ['min' => 100, 'max' => 10],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('config.max');
});

test('updating a question changing its type re-validates config against the new type', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);
    $question = $section->questions()->create(['type' => 'short_text', 'label' => 'Q', 'order' => 0]);

    $response = $this->actingAs($user)->patchJson("/api/v1/survey-questions/{$question->id}", [
        'type' => 'matrix',
        'config' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['config.matrixRows', 'config.matrixColumns']);
});

test('a question can be deleted', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);
    $question = $section->questions()->create(['type' => 'short_text', 'label' => 'Q', 'order' => 0]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/survey-questions/{$question->id}");

    $response->assertOk();
    expect($section->questions()->count())->toBe(0);
});

test('questions can be reordered within a section', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);
    $q1 = $section->questions()->create(['type' => 'short_text', 'label' => 'A', 'order' => 0]);
    $q2 = $section->questions()->create(['type' => 'short_text', 'label' => 'B', 'order' => 1]);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-sections/{$section->id}/questions/reorder", [
        'order' => [$q2->id, $q1->id],
    ]);

    $response->assertOk();
    expect($section->questions()->orderBy('order')->pluck('id')->all())->toBe([$q2->id, $q1->id]);
});

test('single choice question options can be created, updated, and deleted', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);
    $question = $section->questions()->create(['type' => 'single_choice', 'label' => 'Pick one', 'order' => 0]);

    $create = $this->actingAs($user)->postJson("/api/v1/survey-questions/{$question->id}/options", [
        'label' => 'Option A',
    ]);
    $create->assertCreated()->assertJsonPath('data.value', 'Option A');

    $optionId = $create->json('data.id');

    $update = $this->actingAs($user)->patchJson("/api/v1/question-options/{$optionId}", [
        'value' => 'a',
    ]);
    $update->assertOk()->assertJsonPath('data.value', 'a');

    $delete = $this->actingAs($user)->deleteJson("/api/v1/question-options/{$optionId}");
    $delete->assertOk();
    expect($question->options()->count())->toBe(0);
});

test('a user from another organization cannot add a question to this section', function () {
    $user = editorForQuestions();
    $section = draftSectionFor($user);

    $foreignOrg = Organization::factory()->create();
    $foreignUser = User::factory()->create(['organization_id' => $foreignOrg->id]);
    $foreignUser->assignRole('Editor');

    $response = $this->actingAs($foreignUser)->postJson("/api/v1/survey-sections/{$section->id}/questions", [
        'type' => 'short_text',
        'label' => 'Sneaky',
    ]);

    $response->assertForbidden();
});
