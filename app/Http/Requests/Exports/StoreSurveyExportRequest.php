<?php

namespace App\Http\Requests\Exports;

use App\Models\SurveyResponse;
use App\Support\ResponseExportRowBuilder;
use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('export', SurveyResponse::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', 'in:xlsx,csv'],
            'scope' => ['required', 'string', 'in:all,filtered,selected'],
            'version_id' => ['nullable', 'integer'],
            'response_ids' => ['required_if:scope,selected', 'array'],
            'response_ids.*' => ['integer'],
            'filters' => ['required_if:scope,filtered', 'array'],
            'filters.*.question_id' => ['required', 'integer'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['nullable'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string', 'in:'.implode(',', ResponseExportRowBuilder::OPTIONAL_COLUMNS)],
        ];
    }
}
