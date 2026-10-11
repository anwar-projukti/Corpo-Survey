<?php

namespace App\Http\Requests\Imports;

use App\Models\SurveyResponse;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmRespondentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', SurveyResponse::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'column_mapping' => ['required', 'array'],
            'column_mapping.name' => ['nullable', 'string'],
            'column_mapping.email' => ['nullable', 'string'],
            'column_mapping.phone' => ['nullable', 'string'],
        ];
    }
}
