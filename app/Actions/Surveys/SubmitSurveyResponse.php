<?php

namespace App\Actions\Surveys;

use App\Enums\CollectionMethod;
use App\Enums\ResponseStatus;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Support\AnswerValidationRules;
use App\Support\RuleEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validates and persists one respondent's answers to a published survey
 * version (spec §5.8, §6.2). Every check here is server-side and re-derived
 * from the version's own questions/rules — nothing about which questions
 * are required, visible, or what a valid answer looks like is trusted from
 * the client, since the client-side Alpine form's own show/hide/validation
 * logic is only a UX convenience, not a security boundary.
 */
class SubmitSurveyResponse
{
    /**
     * @param  array<int, mixed>  $answers  survey_question_id => raw submitted value
     * @param  array{latitude?: float|null, longitude?: float|null, meta?: array|null}  $context
     */
    public function handle(
        SurveyVersion $version,
        array $answers,
        string $fingerprint,
        array $context = [],
        bool $anonymize = false,
    ): SurveyResponse {
        $questionsById = $version->sections->flatMap->questions->keyBy('id');

        $engine = new RuleEngine($version, $answers);
        $hiddenIds = $engine->hiddenQuestionIds();
        $requiredIds = $engine->requiredQuestionIds();

        // Answers to a hidden/skipped question are dropped rather than
        // validated — a client that still sent one (e.g. a stale form
        // state) shouldn't be able to fail submission over it, and it must
        // never be persisted as if the respondent actually saw it.
        $answers = array_diff_key($answers, array_flip($hiddenIds));

        $this->validateAnswers($questionsById, $answers, $requiredIds);

        // A blank answer to an optional question passes validation (it's
        // simply "not answered") but shouldn't create a real answer row —
        // otherwise every optional question a respondent skips would still
        // leave behind an empty-string/empty-array response_answers row.
        $answers = array_filter(
            $answers,
            fn ($value, $questionId) => ! $this->isBlank($value) || in_array($questionId, $requiredIds, true),
            ARRAY_FILTER_USE_BOTH,
        );

        return DB::transaction(function () use ($version, $answers, $fingerprint, $context, $anonymize, $engine) {
            $response = new SurveyResponse([
                'survey_id' => $version->survey_id,
                'survey_version_id' => $version->id,
                'collection_method' => CollectionMethod::SelfServe,
                'status' => $engine->shouldEndSurvey() ? ResponseStatus::Partial : ResponseStatus::Complete,
                'respondent_fingerprint' => $fingerprint,
                'meta' => $context['meta'] ?? null,
                'latitude' => $context['latitude'] ?? null,
                'longitude' => $context['longitude'] ?? null,
                'started_at' => $context['started_at'] ?? now(),
                'submitted_at' => now(),
            ]);

            $response->forceFill([
                'organization_id' => $version->survey->organization_id,
                'ip_address' => $anonymize ? null : $context['ip_address'] ?? null,
                'user_agent' => $anonymize ? null : $context['user_agent'] ?? null,
            ]);

            $response->save();

            foreach ($answers as $questionId => $value) {
                $response->answers()->create([
                    'survey_question_id' => $questionId,
                    'value' => $value,
                ]);
            }

            return $response->fresh('answers');
        });
    }

    /**
     * @param  Collection<int, SurveyQuestion>  $questionsById
     * @param  array<int, mixed>  $answers
     * @param  array<int, int>  $requiredIds
     */
    private function validateAnswers($questionsById, array $answers, array $requiredIds): void
    {
        $errors = [];

        foreach ($requiredIds as $questionId) {
            if (! array_key_exists($questionId, $answers) || $this->isBlank($answers[$questionId])) {
                $errors["answers.$questionId"] = ['This question is required.'];
            }
        }

        foreach ($answers as $questionId => $value) {
            $question = $questionsById->get($questionId);

            if (! $question) {
                $errors["answers.$questionId"] = ['This question does not belong to this survey.'];

                continue;
            }

            if ($this->isBlank($value) && ! in_array($questionId, $requiredIds, true)) {
                continue;
            }

            $validator = validator(
                ['answer' => $value],
                ['answer' => AnswerValidationRules::forQuestion($question)],
            );

            if ($validator->fails()) {
                $errors["answers.$questionId"] = $validator->errors()->get('answer');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
