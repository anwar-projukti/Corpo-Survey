<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Validates the recursive AND/OR condition tree stored in
 * survey_rules.conditions (spec §6.3). A DTO can't express this shape
 * cleanly since a node is either a leaf condition or a group of more
 * nodes — so this walks the array directly instead.
 *
 * A tree is one of:
 *   leaf:  {"question_id": int, "operator": string, "value": mixed}
 *   group: {"operator": "and"|"or", "conditions": [tree, ...]}
 *
 * For a leaf whose question_id points at an option-based question (spec
 * §6.1 — single/multiple choice, dropdown, ...), `value` must be the
 * matching question_options.value (what a real submitted answer stores),
 * never the option's row id.
 */
class ConditionTree
{
    private const LEAF_OPERATORS = [
        'equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered',
    ];

    /**
     * @param  array<int, int>  $validQuestionIds  question IDs that exist within the same survey version
     *
     * @throws ValidationException
     */
    public static function validate(mixed $tree, array $validQuestionIds, int $depth = 0): void
    {
        if ($depth > 10) {
            throw ValidationException::withMessages(['conditions' => 'Condition tree is nested too deeply.']);
        }

        if (! is_array($tree)) {
            throw ValidationException::withMessages(['conditions' => 'Each condition node must be an object.']);
        }

        if (array_key_exists('conditions', $tree)) {
            self::validateGroup($tree, $validQuestionIds, $depth);

            return;
        }

        self::validateLeaf($tree, $validQuestionIds);
    }

    /**
     * @param  array<int, int>  $validQuestionIds
     */
    private static function validateGroup(array $node, array $validQuestionIds, int $depth): void
    {
        if (! in_array($node['operator'] ?? null, ['and', 'or'], true)) {
            throw ValidationException::withMessages(['conditions' => 'A condition group operator must be "and" or "or".']);
        }

        if (! is_array($node['conditions']) || $node['conditions'] === []) {
            throw ValidationException::withMessages(['conditions' => 'A condition group must contain at least one condition.']);
        }

        foreach ($node['conditions'] as $child) {
            self::validate($child, $validQuestionIds, $depth + 1);
        }
    }

    /**
     * @param  array<int, int>  $validQuestionIds
     */
    private static function validateLeaf(array $node, array $validQuestionIds): void
    {
        if (! isset($node['question_id']) || ! in_array($node['question_id'], $validQuestionIds, true)) {
            throw ValidationException::withMessages(['conditions' => 'A condition must reference a question in the same survey version.']);
        }

        if (! in_array($node['operator'] ?? null, self::LEAF_OPERATORS, true)) {
            throw ValidationException::withMessages(['conditions' => 'Unrecognized condition operator: '.($node['operator'] ?? 'null')]);
        }

        $needsValue = ! in_array($node['operator'], ['is_answered', 'is_not_answered'], true);

        if ($needsValue && ! array_key_exists('value', $node)) {
            throw ValidationException::withMessages(['conditions' => "The \"{$node['operator']}\" operator requires a value."]);
        }
    }
}
