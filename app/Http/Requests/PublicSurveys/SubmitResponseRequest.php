<?php

namespace App\Http\Requests\PublicSurveys;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates only the submission envelope — per-question answer content is
 * validated separately by SubmitSurveyResponse (App\Support\AnswerValidationRules),
 * since those rules depend on each question's own type and config, not
 * anything expressible as a fixed rule set here.
 */
class SubmitResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable'],
            'otp_destination' => ['nullable', 'email', 'max:255'],
            'captcha_token' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'started_at' => ['nullable', 'date'],
        ];
    }
}
