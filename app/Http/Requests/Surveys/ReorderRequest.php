<?php

namespace App\Http\Requests\Surveys;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by every reorder endpoint (sections, questions, options).
 * Authorization is resource-specific, so each controller action still
 * calls its own policy check before invoking ReorderItems — this request
 * only validates the shape of the payload.
 */
class ReorderRequest extends FormRequest
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
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
