<?php

namespace App\Support;

use App\Enums\RuleAction;
use App\Models\SurveyRule;
use App\Models\SurveyVersion;

/**
 * Evaluates a survey version's conditional logic rules (spec §6.3) against
 * a set of already-submitted answers. The client-side Alpine form makes the
 * same show/hide/require decisions for a live respondent, but none of that
 * is trusted — every submission is re-evaluated here so a client can't
 * bypass a hidden required question or fabricate a "required" answer for a
 * question that was actually skipped.
 *
 * A leaf condition's `value` is compared against the raw submitted answer
 * value (a question_options.value for choice-based questions, per
 * ConditionTree's own docblock) — never a question_options row id.
 */
class RuleEngine
{
    private const LEAF_OPERATORS = [
        'equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered',
    ];

    /**
     * @param  array<int, mixed>  $answers  question_id => submitted value
     */
    public function __construct(
        private readonly SurveyVersion $version,
        private readonly array $answers,
    ) {}

    /**
     * Question IDs that should be treated as required for this submission —
     * a question's own is_required flag, adjusted by any active
     * require/make_optional rule whose condition is currently met.
     *
     * @return array<int, int>
     */
    public function requiredQuestionIds(): array
    {
        $required = [];

        foreach ($this->version->sections as $section) {
            foreach ($section->questions as $question) {
                if ($question->is_required && ! $this->isHidden($question->id)) {
                    $required[$question->id] = $question->id;
                }
            }
        }

        foreach ($this->activeRules() as $rule) {
            if ($rule->action === RuleAction::Require) {
                $required[$rule->target['question_id']] = $rule->target['question_id'];
            }

            if ($rule->action === RuleAction::MakeOptional) {
                unset($required[$rule->target['question_id']]);
            }
        }

        return array_values($required);
    }

    /**
     * Question IDs a respondent should never be asked to answer this
     * submission — either explicitly hidden/skipped by a rule, or hidden
     * because their containing section was skipped.
     *
     * @return array<int, int>
     */
    public function hiddenQuestionIds(): array
    {
        $hidden = [];

        foreach ($this->version->sections as $section) {
            foreach ($section->questions as $question) {
                if ($this->isHidden($question->id)) {
                    $hidden[$question->id] = $question->id;
                }
            }
        }

        return array_values($hidden);
    }

    /**
     * True once any active rule's action is end_survey — submission
     * processing for anything after this point should stop.
     */
    public function shouldEndSurvey(): bool
    {
        foreach ($this->activeRules() as $rule) {
            if ($rule->action === RuleAction::EndSurvey) {
                return true;
            }
        }

        return false;
    }

    private function isHidden(int $questionId): bool
    {
        $skippedSectionIds = [];

        foreach ($this->activeRules() as $rule) {
            if ($rule->action === RuleAction::Skip) {
                $skippedSectionIds[] = $rule->target['section_id'];
            }
        }

        foreach ($this->version->sections as $section) {
            if (in_array($section->id, $skippedSectionIds, true)
                && $section->questions->contains('id', $questionId)) {
                return true;
            }
        }

        foreach ($this->activeRules() as $rule) {
            if ($rule->action === RuleAction::Hide && $rule->target['question_id'] === $questionId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, SurveyRule>
     */
    private function activeRules()
    {
        return $this->version->rules
            ->where('is_active', true)
            ->filter(fn ($rule) => $this->conditionMet($rule->conditions));
    }

    private function conditionMet(mixed $node): bool
    {
        if (! is_array($node)) {
            return false;
        }

        if (array_key_exists('conditions', $node)) {
            $results = array_map(fn ($child) => $this->conditionMet($child), $node['conditions']);

            return $node['operator'] === 'or' ? in_array(true, $results, true) : ! in_array(false, $results, true);
        }

        $operator = $node['operator'] ?? null;

        if (! in_array($operator, self::LEAF_OPERATORS, true)) {
            return false;
        }

        $actual = $this->answers[$node['question_id']] ?? null;
        $expected = $node['value'] ?? null;

        return match ($operator) {
            'equals' => self::normalize($actual) == self::normalize($expected),
            'not_equals' => self::normalize($actual) != self::normalize($expected),
            'gt' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'contains' => is_array($actual)
                ? in_array($expected, $actual, false)
                : str_contains((string) $actual, (string) $expected),
            'is_answered' => ! self::isEmpty($actual),
            'is_not_answered' => self::isEmpty($actual),
        };
    }

    private static function normalize(mixed $value): mixed
    {
        return is_array($value) ? $value : (string) $value;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
