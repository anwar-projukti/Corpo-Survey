<?php

namespace App\Http\Requests\Surveys;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('survey'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Survey $survey */
        $survey = $this->route('survey');

        return [
            'user_id' => [
                'required',
                'integer',
                // Scoped to the survey's own organization, not the acting
                // admin's — the two are already guaranteed equal by the
                // "assign" policy ability, but this rule is what actually
                // stops assigning a survey to a user from another tenant
                // if that guarantee were ever weakened.
                Rule::exists('users', 'id')->where('organization_id', $survey->organization_id),
                Rule::unique('survey_assignments', 'user_id')->where('survey_id', $survey->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'That user does not belong to this organization.',
            'user_id.unique' => 'That user is already assigned to this survey.',
        ];
    }
}
