<?php

namespace App\Actions\Responses;

use App\Models\ResponseAnswer;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\AnswerValidationRules;
use Illuminate\Validation\ValidationException;

/**
 * Edits one answer on an already-submitted response (spec §5.11, §8) —
 * historical survey data is never silently modified: every edit is
 * captured in the audit log with the original value, the new value, the
 * question, and the editing user (AuditLogger itself stamps the request's
 * IP/timestamp on every entry, so this doesn't need to duplicate that).
 * A non-blank new value is validated against the same rules a
 * respondent's own answer would have to satisfy
 * (App\Support\AnswerValidationRules), so an edit can't introduce data
 * that couldn't have been submitted legitimately. Blanking an answer is
 * always allowed, even for a question that's required — an admin
 * reviewing responses is trusted to clear clearly-wrong data, and the
 * edit is fully audited either way.
 */
class EditResponseAnswer
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(ResponseAnswer $answer, mixed $newValue, User $actor): ResponseAnswer
    {
        $question = $answer->question;
        $originalValue = $answer->value;

        if (! $this->isBlank($newValue)) {
            $validator = validator(
                ['answer' => $newValue],
                ['answer' => AnswerValidationRules::forQuestion($question)],
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'value' => $validator->errors()->get('answer'),
                ]);
            }
        }

        if ($originalValue === $newValue) {
            return $answer;
        }

        $answer->update(['value' => $newValue]);

        $this->auditLogger->log(
            'response.answer_edited',
            subject: $answer->response,
            properties: [
                'question_id' => $question->id,
                'question_label' => $question->label,
                'before' => $originalValue,
                'after' => $newValue,
            ],
            actor: $actor,
        );

        return $answer->fresh();
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
