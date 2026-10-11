<?php

namespace App\Http\Requests\Users;

use App\Actions\Fortify\PasswordValidationRules;
use App\Support\AssignableRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeammateRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('teammate'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'role' => ['sometimes', 'required', 'string', Rule::in(AssignableRole::all())],
            'password' => ['sometimes', ...$this->passwordRules()],
        ];
    }
}
