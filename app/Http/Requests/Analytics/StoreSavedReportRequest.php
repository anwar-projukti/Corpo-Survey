<?php

namespace App\Http\Requests\Analytics;

use App\Models\SavedReport;
use App\Support\ResponseFilterEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreSavedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [SavedReport::class, $this->route('survey')]);
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
     * The survey's current version's question ids are the only ones a
     * report may reference (spec §6.2 versioning — the same scoping
     * App\Http\Controllers\Concerns\ResolvesSurveyVersion applies
     * elsewhere) and every filter must be independently valid per
     * App\Support\ResponseFilterEngine's own rules, not just present.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $survey = $this->route('survey');
            $version = $survey->currentPublishedVersion ?? $survey->currentDraftVersion;

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
