<?php

namespace App\Http\Requests\Analytics;

use App\Support\ResponseFilterEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('savedReport'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],
            'config.question_ids' => ['required', 'array', 'min:1'],
            'config.question_ids.*' => ['integer'],
            'config.filters' => ['sometimes', 'array'],
            'config.chart_types' => ['sometimes', 'array'],
            'config.chart_types.*' => ['string', 'in:doughnut,bar'],
            'config.date_from' => ['nullable', 'date'],
            'config.date_to' => ['nullable', 'date', 'after_or_equal:config.date_from'],
        ];
    }

    /**
     * Mirrors StoreSavedReportRequest::withValidator() — validates
     * against the existing report's own survey/version instead of a
     * {survey} route parameter, since update goes through
     * PATCH /saved-reports/{savedReport} rather than a survey-scoped URL.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $report = $this->route('savedReport');
            $version = $report->survey->currentPublishedVersion ?? $report->survey->currentDraftVersion;

            if ($version === null) {
                $validator->errors()->add('config.question_ids', 'This survey has no version to report on.');

                return;
            }

            $validQuestionIds = $version->sections()
                ->with('questions:id,survey_section_id')
                ->get()
                ->flatMap->questions
                ->pluck('id')
                ->all();

            $questionIds = $this->input('config.question_ids', []);

            foreach ($questionIds as $questionId) {
                if (! in_array($questionId, $validQuestionIds, true)) {
                    $validator->errors()->add('config.question_ids', "Question {$questionId} does not belong to this survey's current version.");
                }
            }

            $filters = $this->input('config.filters', []);

            if ($filters !== []) {
                try {
                    ResponseFilterEngine::validate($filters, $validQuestionIds);
                } catch (ValidationException $e) {
                    foreach ($e->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add("config.$field", $message);
                        }
                    }
                }
            }
        });
    }
}
