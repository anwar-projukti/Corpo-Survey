<?php

namespace App\Support;

use App\Models\ResponseAnswer;
use App\Models\SurveyResponse;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Filters survey_responses by answer values (spec §8.2) — the same filter
 * shape is meant to be reused across the response table, analytics, and
 * exports (spec §9), so this takes a plain SurveyResponse query builder
 * and narrows it, rather than being wired into any one controller.
 *
 * A filter set is a flat list of conditions, ANDed together (unlike
 * App\Support\ConditionTree's recursive AND/OR groups for conditional
 * logic — response filtering only ever needs a flat AND list in practice,
 * per the spec's own example: "Age > 25 AND Location = Dhaka AND
 * Satisfaction = Very Satisfied"):
 *
 *   [
 *     ['question_id' => 12, 'operator' => 'gt', 'value' => 25],
 *     ['question_id' => 7, 'operator' => 'equals', 'value' => 'Dhaka'],
 *   ]
 *
 * Operators mirror App\Support\RuleEngine's vocabulary so a builder admin
 * only has to learn one operator set across conditional logic and
 * response filtering: equals, not_equals, gt, gte, lt, lte, contains,
 * is_answered, is_not_answered.
 */
class ResponseFilterEngine
{
    private const OPERATORS = [
        'equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered',
    ];

    /**
     * @param  array<int, array{question_id: int, operator: string, value?: mixed}>  $filters
     * @param  array<int, int>  $validQuestionIds  question IDs that exist within the survey being filtered
     */
    public static function validate(array $filters, array $validQuestionIds): void
    {
        foreach ($filters as $index => $filter) {
            // Filters arriving over a query string (GET .../responses?filters[0][question_id]=5)
            // have every scalar as a string — an int-keyed strict
            // in_array() against $validQuestionIds would otherwise reject
            // every legitimate filter, since "5" !== 5.
            $questionId = isset($filter['question_id']) && is_numeric($filter['question_id'])
                ? (int) $filter['question_id']
                : null;

            if ($questionId === null || ! in_array($questionId, $validQuestionIds, true)) {
                throw ValidationException::withMessages([
                    "filters.$index.question_id" => 'Must reference a question in this survey.',
                ]);
            }

            if (! in_array($filter['operator'] ?? null, self::OPERATORS, true)) {
                throw ValidationException::withMessages([
                    "filters.$index.operator" => 'Unrecognized filter operator: '.($filter['operator'] ?? 'null'),
                ]);
            }

            $needsValue = ! in_array($filter['operator'], ['is_answered', 'is_not_answered'], true);

            if ($needsValue && ! array_key_exists('value', $filter)) {
                throw ValidationException::withMessages([
                    "filters.$index.value" => "The \"{$filter['operator']}\" operator requires a value.",
                ]);
            }
        }
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     * @param  array<int, array{question_id: int, operator: string, value?: mixed}>  $filters
     * @return Builder<SurveyResponse>
     */
    public static function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            self::applyOne($query, $filter);
        }

        return $query;
    }

    /**
     * @param  Builder<SurveyResponse>  $query
     * @param  array{question_id: int, operator: string, value?: mixed}  $filter
     */
    private static function applyOne(Builder $query, array $filter): void
    {
        $questionId = (int) $filter['question_id'];
        $operator = $filter['operator'];
        $value = $filter['value'] ?? null;

        // is_not_answered is the one operator that must match a response
        // with NO answer row at all for this question, not just a row
        // whose value happens to be blank — it can never be expressed as
        // an extra condition inside whereHas('answers', ...), since that
        // clause only ever narrows *existing* rows.
        if ($operator === 'is_not_answered') {
            $query->whereDoesntHave('answers', function ($answers) use ($questionId) {
                $answers->where('survey_question_id', $questionId)
                    ->whereNotNull('value')
                    ->where('value', '!=', json_encode(''));
            });

            return;
        }

        $query->whereHas('answers', function ($answers) use ($questionId, $operator, $value) {
            $answers->where('survey_question_id', $questionId);

            match ($operator) {
                'equals' => $answers->where('value', json_encode($value)),
                'not_equals' => $answers->where('value', '!=', json_encode($value)),
                'gt' => $answers->whereRaw('CAST(response_answers.value AS DECIMAL(20,6)) > ?', [$value]),
                'gte' => $answers->whereRaw('CAST(response_answers.value AS DECIMAL(20,6)) >= ?', [$value]),
                'lt' => $answers->whereRaw('CAST(response_answers.value AS DECIMAL(20,6)) < ?', [$value]),
                'lte' => $answers->whereRaw('CAST(response_answers.value AS DECIMAL(20,6)) <= ?', [$value]),
                'contains' => self::applyContains($answers, $value),
                'is_answered' => $answers->whereNotNull('value')->where('value', '!=', json_encode('')),
                default => null,
            };
        });
    }

    /**
     * whereJsonContains() is MySQL-safe on its own, but its SQLite
     * compilation (an unqualified `json_each("value")` inside a
     * correlated EXISTS subquery) silently matches nothing — the column
     * reference doesn't resolve back to the outer query the way it does
     * for a simple top-level query. A driver-specific, explicitly
     * table-qualified raw fragment sidesteps that for both drivers this
     * app actually runs on.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ResponseAnswer>  $answers
     */
    private static function applyContains($answers, mixed $value): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $answers->whereRaw(
                'exists (select 1 from json_each(response_answers.value) je where je.value = ?)',
                [$value],
            );

            return;
        }

        $answers->whereRaw('json_contains(response_answers.value, ?)', [json_encode($value)]);
    }
}
