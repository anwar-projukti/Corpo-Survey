<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class AnonymizeAccountRequest extends FormRequest
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
            // Re-confirming the current password is the only guard on an
            // otherwise irreversible, destructive self-service action —
            // the same reasoning Fortify's own password-confirmation
            // middleware applies before 2FA setup changes.
            'password' => ['required', 'current_password'],
        ];
    }
}
