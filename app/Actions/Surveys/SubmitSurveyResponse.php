<?php

namespace App\Actions\Surveys;

use App\Enums\CollectionMethod;
use App\Enums\ResponseStatus;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Support\AnswerValidationRules;
use App\Support\RuleEngine;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validates and persists one respondent's answers to a published survey
 * version (spec §5.8, §6.2, §7). Every check here is server-side and
 * re-derived from the version's own questions/rules — nothing about which
 * questions are required, visible, or what a valid answer looks like is
 * trusted from the client, since the client-side Alpine form's own
 * show/hide/validation logic is only a UX convenience, not a security
 * boundary.
 *
 * Two collection paths share this one action: a public/anonymous
 * respondent (no $collector, tracked instead by $fingerprint — the signed
 * respondent cookie from PublicSurveyAccess) and a logged-in field user
 * going door-to-door (spec §7 — $collector is the authenticated user,
 * never trusted from any client-supplied field, and there is no
 * respondent-cookie fingerprint/duplicate-policy concept for that path at
 * all, since the same field user legitimately submits many responses).
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
        ?string $fingerprint = null,
        array $context = [],
        bool $anonymize = false,
        ?User $collector = null,
        ?string $clientSubmissionId = null,
    ): SurveyResponse {
        // A field user's device retrying a queued submission after a
        // dropped connection (spec §7's offline queueing) must not create
        // a second response — the unique (collected_by_user_id,
        // client_submission_id) index is what actually prevents the
        // duplicate row; this is just returning the same result the first
        // attempt already produced instead of a confusing error.
        if ($collector && $clientSubmissionId) {
            $existing = SurveyResponse::query()
                ->where('collected_by_user_id', $collector->id)
                ->where('client_submission_id', $clientSubmissionId)
                ->first();

            if ($existing) {
                return $existing->load('answers');
            }
        }

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

        return DB::transaction(function () use ($version, $answers, $fingerprint, $context, $anonymize, $engine, $collector, $clientSubmissionId) {
            $response = new SurveyResponse([
                'survey_id' => $version->survey_id,
                'survey_version_id' => $version->id,
                'collection_method' => $collector ? CollectionMethod::FieldAgent : CollectionMethod::SelfServe,
                'status' => $engine->shouldEndSurvey() ? ResponseStatus::Partial : ResponseStatus::Complete,
                // A field agent's own submissions are never subject to the
                // public respondent-cookie duplicate policy (spec §7 —
                // the same collector legitimately submits many responses),
                // so this stays null for that path regardless of what the
                // caller passed.
                'respondent_fingerprint' => $collector ? null : $fingerprint,
                'meta' => $context['meta'] ?? null,
                'latitude' => $context['latitude'] ?? null,
                'longitude' => $context['longitude'] ?? null,
                'started_at' => $context['started_at'] ?? now(),
                'submitted_at' => now(),
            ]);

            $response->forceFill([
                'organization_id' => $version->survey->organization_id,
                'collected_by_user_id' => $collector?->id,
                'client_submission_id' => $collector ? $clientSubmissionId : null,
                // A field agent's own device/network is not the
                // respondent's — recording it would misattribute PII-
                // adjacent metadata to the wrong party, so it's dropped
                // the same way anonymize does for the public path.
                'ip_address' => ($anonymize || $collector) ? null : $context['ip_address'] ?? null,
                'user_agent' => ($anonymize || $collector) ? null : $context['user_agent'] ?? null,
            ]);

            try {
                $response->save();
            } catch (UniqueConstraintViolationException $exception) {
                // Lost a race against another request carrying the exact
                // same client_submission_id (the pre-check above only
                // catches a sequential retry, not a concurrent one — e.g.
                // a flaky connection causing the client to fire the same
                // queued submission twice in quick succession). The row
                // that won is what should be returned either way.
                if (! $collector || ! $clientSubmissionId) {
                    throw $exception;
                }

                return SurveyResponse::query()
                    ->where('collected_by_user_id', $collector->id)
                    ->where('client_submission_id', $clientSubmissionId)
                    ->firstOrFail()
                    ->load('answers');
            }

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
