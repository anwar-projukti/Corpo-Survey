<?php

namespace App\Data;

use App\Enums\QuestionType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

/**
 * The shape of survey_questions.config. One flexible DTO rather than ~30
 * per-type classes: every field is optional, and rules() narrows which
 * fields apply based on the question's type (passed in via context, since
 * the type itself lives on the question record, not inside config).
 *
 * This is what stands between "arbitrary JSON a client posted" and
 * "trusted config a later phase's builder/renderer can rely on" — never
 * skip validating through this DTO when writing survey_questions.config.
 */
class QuestionConfigData extends Data
{
    public function __construct(
        // Text / number bounds (short_text, long_text, number, slider, range, ...)
        public int|Optional|null $min = null,
        public int|Optional|null $max = null,
        public int|Optional|null $step = null,
        public int|Optional|null $maxLength = null,

        // Placeholder / formatting hints shown in the field itself.
        public string|Optional|null $placeholder = null,

        // Rating-family (star, numeric_rating, emoji, likert, nps, satisfaction)
        public int|Optional|null $scaleMin = null,
        public int|Optional|null $scaleMax = null,
        /** @var array<int, string>|Optional|null */
        public array|Optional|null $scaleLabels = null,

        // Matrix: rows and columns are each a flat label list; the answer
        // is a row×column grid. Options (question_options) aren't used for
        // matrix — this is the one type that needs two axes, not one list.
        /** @var array<int, string>|Optional|null */
        public array|Optional|null $matrixRows = null,
        /** @var array<int, string>|Optional|null */
        public array|Optional|null $matrixColumns = null,

        // Selection-family: how many choices are allowed (multiple_choice),
        // whether "other" is allowed as a free-text fallback option.
        public int|Optional|null $minSelections = null,
        public int|Optional|null $maxSelections = null,
        public bool|Optional|null $allowOther = null,

        // File / image upload (spec §5.9 — enforced again server-side on
        // upload, this is only the builder's stated intent).
        public int|Optional|null $maxFileSizeKb = null,
        /** @var array<int, string>|Optional|null */
        public array|Optional|null $allowedMimeTypes = null,

        // Location (spec §6.1 Location questions).
        public bool|Optional|null $restrictToCountry = null,
        public string|Optional|null $defaultCountry = null,
    ) {}

    /**
     * Validation rules narrowed by question type. $type is passed via
     * Data's context mechanism (see QuestionConfigData::rulesForType),
     * not a constructor property — it belongs to the question, not the config.
     *
     * @return array<string, array<int, string>>
     */
    public static function rulesForType(QuestionType $type): array
    {
        $rules = [
            'min' => ['sometimes', 'integer'],
            'max' => ['sometimes', 'integer', 'gte:min'],
            'step' => ['sometimes', 'integer', 'min:1'],
            'maxLength' => ['sometimes', 'integer', 'min:1'],
            'placeholder' => ['sometimes', 'string', 'max:255'],
        ];

        $ratingTypes = [
            QuestionType::Star, QuestionType::NumericRating, QuestionType::Emoji,
            QuestionType::Likert, QuestionType::Nps, QuestionType::Satisfaction,
        ];

        if (in_array($type, $ratingTypes, true)) {
            $rules += [
                'scaleMin' => ['sometimes', 'integer'],
                'scaleMax' => ['sometimes', 'integer', 'gt:scaleMin'],
                'scaleLabels' => ['sometimes', 'array'],
                'scaleLabels.*' => ['string', 'max:100'],
            ];
        }

        if ($type === QuestionType::Matrix) {
            $rules += [
                'matrixRows' => ['required', 'array', 'min:1'],
                'matrixRows.*' => ['string', 'max:255'],
                'matrixColumns' => ['required', 'array', 'min:1'],
                'matrixColumns.*' => ['string', 'max:255'],
            ];
        }

        if ($type->usesOptions()) {
            $rules += [
                'minSelections' => ['sometimes', 'integer', 'min:0'],
                'maxSelections' => ['sometimes', 'integer', 'gte:minSelections'],
                'allowOther' => ['sometimes', 'boolean'],
            ];
        }

        if ($type->isFileType()) {
            $rules += [
                'maxFileSizeKb' => ['sometimes', 'integer', 'min:1', 'max:102400'],
                'allowedMimeTypes' => ['sometimes', 'array'],
                'allowedMimeTypes.*' => ['string'],
            ];
        }

        if (in_array($type, [QuestionType::Country, QuestionType::Division, QuestionType::District, QuestionType::City], true)) {
            $rules += [
                'restrictToCountry' => ['sometimes', 'boolean'],
                'defaultCountry' => ['sometimes', 'string', 'size:2'],
            ];
        }

        return $rules;
    }
}
