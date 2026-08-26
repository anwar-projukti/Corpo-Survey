<?php

namespace App\Http\Requests\Responses;

use App\Enums\ResponseReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewResponseRequest extends FormRequest
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
            'review_status' => ['required', Rule::enum(ResponseReviewStatus::class)],
        ];
    }
}
