<?php

namespace App\Http\Requests\Responses;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('response'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
