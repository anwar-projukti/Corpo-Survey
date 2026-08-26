<?php

namespace App\Http\Requests\My;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates only the submission envelope for a door-to-door response
 * (spec §7) — per-question answer content is validated separately by
 * SubmitSurveyResponse (App\Support\AnswerValidationRules), the same as
 * the public submission path.
 */
class StoreMyResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->assignedSurveys()->where('surveys.id', $this->route('survey')->id)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'started_at' => ['nullable', 'date'],
            // Only present on a request replayed from the offline queue
            // (resources/js/field/offline-queue.js) — lets the client
            // recognize "this exact queued submission already made it to
            // the server" on a retry after a flaky connection, without
            // the server needing to guess from answer content alone.
            'client_submission_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
