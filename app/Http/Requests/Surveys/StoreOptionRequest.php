<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('question')->section->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
