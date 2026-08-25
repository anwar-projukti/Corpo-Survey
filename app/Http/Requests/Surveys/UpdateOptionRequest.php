<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('option')->question->section->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
