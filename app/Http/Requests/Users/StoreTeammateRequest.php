<?php

namespace App\Http\Requests\Users;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use App\Support\AssignableRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeammateRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::in(AssignableRole::all())],
        ];
    }
}
