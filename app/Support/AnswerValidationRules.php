<?php

namespace App\Support;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Validation rules for one respondent-submitted answer, derived from a
 * question's type and its builder-configured config (spec §6.1/§6.2).
 * Mirrors QuestionConfigData::rulesForType(), but for the *answer* a
 * respondent submits rather than the *config* an admin builds — a
 * question's config narrows what counts as a valid answer (e.g. a
 * single_choice answer must be one of its own question_options values),
 * so this always needs the live question, not just its type.
 *
 * Required-ness is intentionally NOT decided here — RuleEngine determines
 * which questions are required for a given submission (a question's own
 * is_required flag can be overridden by conditional logic), so the caller
 * prepends 'required'/'nullable' itself based on that.
 */
class AnswerValidationRules
{
    /**
     * @return array<int, string|ValidationRule|\Closure>
     */
    public static function forQuestion(SurveyQuestion $question): array
    {
        $config = $question->configData;

        return match ($question->type) {
            QuestionType::ShortText, QuestionType::LongText => [
                'string',
                ...self::maxLengthRule($config->maxLength),
            ],
            QuestionType::Number, QuestionType::Slider, QuestionType::Range => [
                'numeric',
                ...self::boundsRules($config->min, $config->max),
            ],
            QuestionType::Email => ['string', 'email', 'max:255'],
            QuestionType::Phone => ['string', 'max:30'],
            QuestionType::Url => ['string', 'url', 'max:2048'],

            QuestionType::SingleChoice, QuestionType::Dropdown, QuestionType::SearchableDropdown => [
                'string', self::inOptions($question),
            ],
            QuestionType::YesNo => ['in:yes,no'],
            QuestionType::MultipleChoice, QuestionType::ImageChoice => [
                'array',
                ...self::selectionCountRules($config->minSelections, $config->maxSelections),
            ],
            QuestionType::Ranking => ['array', self::exactPermutationOfOptions($question)],

            QuestionType::Star, QuestionType::NumericRating, QuestionType::Nps => [
                'integer',
                ...self::boundsRules($config->scaleMin, $config->scaleMax),
            ],
            QuestionType::Emoji, QuestionType::Likert, QuestionType::Satisfaction => ['string', 'max:100'],

            QuestionType::Matrix => ['array', self::matrixShape($config->matrixRows, $config->matrixColumns)],

            QuestionType::Date => ['date_format:Y-m-d'],
            QuestionType::Time => ['date_format:H:i'],
            QuestionType::DateTime => ['date'],

            QuestionType::FileUpload, QuestionType::ImageUpload, QuestionType::Signature => ['string', 'max:2048'],

            QuestionType::Country, QuestionType::Division, QuestionType::District, QuestionType::City => ['string', 'max:255'],
            QuestionType::Address => ['string', 'max:1000'],
            QuestionType::MapLocation => ['array', self::latLngShape()],
        };
    }

    /**
     * @return array<int, string>
     */
    private static function maxLengthRule(?int $maxLength): array
    {
        return $maxLength ? ["max:$maxLength"] : [];
    }

    /**
     * @return array<int, string>
     */
    private static function boundsRules(?int $min, ?int $max): array
    {
        $rules = [];

        if ($min !== null) {
            $rules[] = "min:$min";
        }

        if ($max !== null) {
            $rules[] = "max:$max";
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private static function selectionCountRules(?int $min, ?int $max): array
    {
        $rules = [];

        if ($min !== null) {
            $rules[] = "min:$min";
        }

        if ($max !== null) {
            $rules[] = "max:$max";
        }

        return $rules;
    }

    private static function inOptions(SurveyQuestion $question): In
    {
        return Rule::in($question->options->pluck('value')->all());
    }

    /**
     * A ranking answer must be every one of the question's options, each
     * exactly once, in some order — not a subset, not a repeat.
     */
    private static function exactPermutationOfOptions(SurveyQuestion $question): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($question) {
            $expected = $question->options->pluck('value')->all();

            $isValidPermutation = is_array($value)
                && count($value) === count($expected)
                && count(array_unique($value)) === count($value)
                && array_diff($expected, $value) === [];

            if (! $isValidPermutation) {
                $fail('The :attribute must rank every option exactly once.');
            }
        };
    }

    private static function matrixShape(?array $rows, ?array $columns): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($rows, $columns) {
            if (! is_array($value)) {
                $fail('The :attribute must be an object mapping each row to a column.');

                return;
            }

            foreach ($value as $row => $column) {
                // JSON object keys always decode as strings, but a
                // numeric-looking key (e.g. "1") is cast back to an int by
                // PHP's array machinery — compare loosely so a row label
                // like "1" still matches.
                if ($rows !== null && ! in_array($row, $rows, false)) {
                    $fail("The :attribute contains an unknown row \"$row\".");

                    return;
                }

                if ($columns !== null && ! in_array($column, $columns, true)) {
                    $fail("The :attribute contains an unknown column \"$column\" for row \"$row\".");

                    return;
                }
            }
        };
    }

    private static function latLngShape(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! is_array($value) || ! is_numeric($value['lat'] ?? null) || ! is_numeric($value['lng'] ?? null)) {
                $fail('The :attribute must include numeric lat and lng values.');
            }
        };
    }
}
