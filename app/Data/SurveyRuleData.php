<?php

namespace App\Data;

use App\Enums\RuleAction;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * The shape of a survey_rules row (minus `conditions`, which is validated
 * separately by App\Support\ConditionTree — its recursive AND/OR shape
 * doesn't fit a fixed DTO).
 *
 * $action is natively typed as the RuleAction backed enum (laravel-data
 * casts/validates it automatically) rather than string + a validation
 * attribute — SurveyRule::casts() casts the same column to RuleAction, so
 * a plain string here would force a manual RuleAction::from() at every
 * call site that compares the two.
 */
class SurveyRuleData extends Data
{
    public function __construct(
        public string|Optional|null $name,

        public RuleAction $action,

        /** @var array{question_id?: int, section_id?: int}|Optional|null */
        public array|Optional|null $target = null,

        public int $order = 0,
        public bool $is_active = true,
    ) {}
}
