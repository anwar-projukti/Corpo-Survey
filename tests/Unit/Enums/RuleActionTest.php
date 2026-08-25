<?php

use App\Enums\RuleAction;

test('every action except end_survey requires a target', function () {
    expect(RuleAction::Show->requiresTarget())->toBeTrue()
        ->and(RuleAction::Hide->requiresTarget())->toBeTrue()
        ->and(RuleAction::Skip->requiresTarget())->toBeTrue()
        ->and(RuleAction::GoToSection->requiresTarget())->toBeTrue()
        ->and(RuleAction::Require->requiresTarget())->toBeTrue()
        ->and(RuleAction::MakeOptional->requiresTarget())->toBeTrue()
        ->and(RuleAction::EndSurvey->requiresTarget())->toBeFalse();
});

test('question-targeting actions report the correct target type', function () {
    expect(RuleAction::Show->targetType())->toBe('question')
        ->and(RuleAction::Hide->targetType())->toBe('question')
        ->and(RuleAction::Require->targetType())->toBe('question')
        ->and(RuleAction::MakeOptional->targetType())->toBe('question');
});

test('section-targeting actions report the correct target type', function () {
    expect(RuleAction::Skip->targetType())->toBe('section')
        ->and(RuleAction::GoToSection->targetType())->toBe('section');
});

test('end_survey has no target type', function () {
    expect(RuleAction::EndSurvey->targetType())->toBeNull();
});
