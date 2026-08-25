<?php

use App\Models\Organization;
use App\Models\Survey;
use App\Models\User;

function draftVersionWithQuestions(User $user): array
{
    $survey = Survey::factory()->create(['organization_id' => $user->organization_id]);
    $version = $survey->versions()->create(['version_number' => 1, 'status' => 'draft']);
    $survey->setCurrentDraftVersion($version);
    $section = $version->sections()->create(['title' => 'S', 'order' => 0]);
    $q1 = $section->questions()->create(['type' => 'number', 'label' => 'Age', 'order' => 0]);
    $q2 = $section->questions()->create(['type' => 'short_text', 'label' => 'Follow-up', 'order' => 1]);

    return [$version, $q1, $q2];
}

function editorForRules(): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $user->assignRole('Editor');

    return $user;
}

test('a valid rule can be created', function () {
    $user = editorForRules();
    [$version, $q1, $q2] = draftVersionWithQuestions($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/rules", [
        'name' => 'Skip if underage',
        'conditions' => ['question_id' => $q1->id, 'operator' => 'lt', 'value' => 18],
        'action' => 'skip',
        'target' => ['section_id' => $version->sections()->first()->id],
    ]);

    $response->assertCreated()->assertJsonPath('data.action', 'skip');
});

test('a rule referencing a question outside the version is rejected', function () {
    $user = editorForRules();
    [$version, $q1] = draftVersionWithQuestions($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/rules", [
        'conditions' => ['question_id' => 999999, 'operator' => 'equals', 'value' => 'x'],
        'action' => 'show',
        'target' => ['question_id' => $q1->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('conditions');
});

test('an action requiring a target fails without one', function () {
    $user = editorForRules();
    [$version, $q1] = draftVersionWithQuestions($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/rules", [
        'conditions' => ['question_id' => $q1->id, 'operator' => 'is_answered'],
        'action' => 'show',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('target');
});

test('end_survey does not require a target', function () {
    $user = editorForRules();
    [$version, $q1] = draftVersionWithQuestions($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/rules", [
        'conditions' => ['question_id' => $q1->id, 'operator' => 'is_answered'],
        'action' => 'end_survey',
    ]);

    $response->assertCreated();
});

test('a rule can be updated', function () {
    $user = editorForRules();
    [$version, $q1, $q2] = draftVersionWithQuestions($user);
    $rule = $version->rules()->create([
        'conditions' => ['question_id' => $q1->id, 'operator' => 'gte', 'value' => 18],
        'action' => 'show',
        'target' => ['question_id' => $q2->id],
        'order' => 0,
    ]);

    $response = $this->actingAs($user)->patchJson("/api/v1/survey-rules/{$rule->id}", [
        'is_active' => false,
    ]);

    $response->assertOk()->assertJsonPath('data.is_active', false);
});

test('a rule can be deleted', function () {
    $user = editorForRules();
    [$version, $q1, $q2] = draftVersionWithQuestions($user);
    $rule = $version->rules()->create([
        'conditions' => ['question_id' => $q1->id, 'operator' => 'gte', 'value' => 18],
        'action' => 'show',
        'target' => ['question_id' => $q2->id],
        'order' => 0,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/v1/survey-rules/{$rule->id}");

    $response->assertOk();
    expect($version->rules()->count())->toBe(0);
});

test('a malicious operator string is rejected', function () {
    $user = editorForRules();
    [$version, $q1] = draftVersionWithQuestions($user);

    $response = $this->actingAs($user)->postJson("/api/v1/survey-versions/{$version->id}/rules", [
        'conditions' => ['question_id' => $q1->id, 'operator' => '1=1; DROP TABLE users;', 'value' => 'x'],
        'action' => 'show',
        'target' => ['question_id' => $q1->id],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('conditions');
});
