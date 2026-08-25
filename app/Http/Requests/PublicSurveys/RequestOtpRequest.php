<?php

namespace App\Http\Requests\PublicSurveys;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
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
            'destination' => ['required', 'email', 'max:255'],
            'captcha_token' => ['nullable', 'string'],
        ];
    }
}
