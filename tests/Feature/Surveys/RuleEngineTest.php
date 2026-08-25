<?php

use App\Enums\QuestionType;
use App\Enums\RuleAction;
use App\Models\SurveyVersion;
use App\Support\RuleEngine;

function buildVersionWithQuestions(int $count = 2, array $questionOverrides = []): array
{
    $version = SurveyVersion::factory()->create(['settings' => []]);
    $section = $version->sections()->create(['title' => 'Section', 'order' => 0]);

    $questions = [];
    foreach (range(1, $count) as $i) {
        $questions[] = $section->questions()->create([
            'type' => QuestionType::ShortText,
            'label' => "Question $i",
            'order' => $i,
            'is_required' => false,
            ...($questionOverrides[$i - 1] ?? []),
        ]);
    }

    return [$version->fresh(['sections.questions', 'rules']), $section, $questions];
}

test('a question with is_required true is required with no rules present', function () {
    [$version, , $questions] = buildVersionWithQuestions(1, [['is_required' => true]]);

    $engine = new RuleEngine($version, []);

    expect($engine->requiredQuestionIds())->toContain($questions[0]->id);
});

test('a require rule makes an optional question required when its condition is met', function () {
    [$version, , $questions] = buildVersionWithQuestions(2);

    $version->rules()->create([
        'name' => 'Require Q2 if Q1 is yes',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'yes'],
        'action' => RuleAction::Require,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $metEngine = new RuleEngine($version, [$questions[0]->id => 'yes']);
    $unmetEngine = new RuleEngine($version, [$questions[0]->id => 'no']);

    expect($metEngine->requiredQuestionIds())->toContain($questions[1]->id)
        ->and($unmetEngine->requiredQuestionIds())->not->toContain($questions[1]->id);
});

test('a make_optional rule overrides a questions own is_required flag when met', function () {
    [$version, , $questions] = buildVersionWithQuestions(2, [[], ['is_required' => true]]);

    $version->rules()->create([
        'name' => 'Make Q2 optional if Q1 is skip',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'skip'],
        'action' => RuleAction::MakeOptional,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $engine = new RuleEngine($version, [$questions[0]->id => 'skip']);

    expect($engine->requiredQuestionIds())->not->toContain($questions[1]->id);
});

test('a hide rule removes a question from both required and answerable sets when met', function () {
    [$version, , $questions] = buildVersionWithQuestions(2, [[], ['is_required' => true]]);

    $version->rules()->create([
        'name' => 'Hide Q2 if Q1 is no',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'no'],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $engine = new RuleEngine($version, [$questions[0]->id => 'no']);

    expect($engine->hiddenQuestionIds())->toContain($questions[1]->id)
        ->and($engine->requiredQuestionIds())->not->toContain($questions[1]->id);
});

test('a skip rule hides every question in the targeted section', function () {
    $version = SurveyVersion::factory()->create(['settings' => []]);
    $sectionA = $version->sections()->create(['title' => 'A', 'order' => 0]);
    $sectionB = $version->sections()->create(['title' => 'B', 'order' => 1]);

    $trigger = $sectionA->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Trigger', 'order' => 0, 'is_required' => false]);
    $skippedQuestion = $sectionB->questions()->create(['type' => QuestionType::ShortText, 'label' => 'Skipped', 'order' => 0, 'is_required' => true]);

    $version->rules()->create([
        'name' => 'Skip section B',
        'conditions' => ['question_id' => $trigger->id, 'operator' => 'equals', 'value' => 'skip-b'],
        'action' => RuleAction::Skip,
        'target' => ['section_id' => $sectionB->id],
        'order' => 0,
        'is_active' => true,
    ]);

    $engine = new RuleEngine($version->fresh(['sections.questions', 'rules']), [$trigger->id => 'skip-b']);

    expect($engine->hiddenQuestionIds())->toContain($skippedQuestion->id)
        ->and($engine->requiredQuestionIds())->not->toContain($skippedQuestion->id);
});

test('an inactive rule never applies even when its condition would be met', function () {
    [$version, , $questions] = buildVersionWithQuestions(2, [[], ['is_required' => true]]);

    $version->rules()->create([
        'name' => 'Disabled hide rule',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'no'],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => false,
    ]);
    $version->load('rules');

    $engine = new RuleEngine($version, [$questions[0]->id => 'no']);

    expect($engine->hiddenQuestionIds())->not->toContain($questions[1]->id);
});

test('an and group requires every child condition to be met', function () {
    [$version, , $questions] = buildVersionWithQuestions(3);

    $version->rules()->create([
        'name' => 'Hide Q3 if Q1=a and Q2=b',
        'conditions' => [
            'operator' => 'and',
            'conditions' => [
                ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'a'],
                ['question_id' => $questions[1]->id, 'operator' => 'equals', 'value' => 'b'],
            ],
        ],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[2]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $bothMet = new RuleEngine($version, [$questions[0]->id => 'a', $questions[1]->id => 'b']);
    $onlyOneMet = new RuleEngine($version, [$questions[0]->id => 'a', $questions[1]->id => 'x']);

    expect($bothMet->hiddenQuestionIds())->toContain($questions[2]->id)
        ->and($onlyOneMet->hiddenQuestionIds())->not->toContain($questions[2]->id);
});

test('an or group requires only one child condition to be met', function () {
    [$version, , $questions] = buildVersionWithQuestions(3);

    $version->rules()->create([
        'name' => 'Hide Q3 if Q1=a or Q2=b',
        'conditions' => [
            'operator' => 'or',
            'conditions' => [
                ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'a'],
                ['question_id' => $questions[1]->id, 'operator' => 'equals', 'value' => 'b'],
            ],
        ],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[2]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $engine = new RuleEngine($version, [$questions[0]->id => 'a', $questions[1]->id => 'z']);

    expect($engine->hiddenQuestionIds())->toContain($questions[2]->id);
});

test('is_answered and is_not_answered operators check presence rather than a specific value', function () {
    [$version, , $questions] = buildVersionWithQuestions(2);

    $version->rules()->create([
        'name' => 'Hide Q2 if Q1 is answered',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'is_answered'],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $answered = new RuleEngine($version, [$questions[0]->id => 'something']);
    $unanswered = new RuleEngine($version, []);

    expect($answered->hiddenQuestionIds())->toContain($questions[1]->id)
        ->and($unanswered->hiddenQuestionIds())->not->toContain($questions[1]->id);
});

test('an end_survey rule reports shouldEndSurvey true only when its condition is met', function () {
    [$version, , $questions] = buildVersionWithQuestions(1);

    $version->rules()->create([
        'name' => 'End survey if disqualified',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'equals', 'value' => 'disqualify'],
        'action' => RuleAction::EndSurvey,
        'target' => null,
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $met = new RuleEngine($version, [$questions[0]->id => 'disqualify']);
    $unmet = new RuleEngine($version, [$questions[0]->id => 'continue']);

    expect($met->shouldEndSurvey())->toBeTrue()
        ->and($unmet->shouldEndSurvey())->toBeFalse();
});

test('gt gte lt lte operators compare numerically and ignore non numeric answers', function () {
    [$version, , $questions] = buildVersionWithQuestions(2);

    $version->rules()->create([
        'name' => 'Hide Q2 if Q1 > 10',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'gt', 'value' => 10],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $numericMet = new RuleEngine($version, [$questions[0]->id => 15]);
    $numericUnmet = new RuleEngine($version, [$questions[0]->id => 5]);
    $nonNumeric = new RuleEngine($version, [$questions[0]->id => 'not-a-number']);

    expect($numericMet->hiddenQuestionIds())->toContain($questions[1]->id)
        ->and($numericUnmet->hiddenQuestionIds())->not->toContain($questions[1]->id)
        ->and($nonNumeric->hiddenQuestionIds())->not->toContain($questions[1]->id);
});

test('contains operator checks a multi select array answer for the expected value', function () {
    [$version, , $questions] = buildVersionWithQuestions(2);

    $version->rules()->create([
        'name' => 'Hide Q2 if Q1 contains other',
        'conditions' => ['question_id' => $questions[0]->id, 'operator' => 'contains', 'value' => 'other'],
        'action' => RuleAction::Hide,
        'target' => ['question_id' => $questions[1]->id],
        'order' => 0,
        'is_active' => true,
    ]);
    $version->load('rules');

    $contains = new RuleEngine($version, [$questions[0]->id => ['red', 'other']]);
    $doesNotContain = new RuleEngine($version, [$questions[0]->id => ['red', 'blue']]);

    expect($contains->hiddenQuestionIds())->toContain($questions[1]->id)
        ->and($doesNotContain->hiddenQuestionIds())->not->toContain($questions[1]->id);
});
