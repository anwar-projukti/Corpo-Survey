<?php

namespace App\Http\Requests\Responses;

use App\Models\SurveyResponse;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates only the envelope — the answer's actual content is validated
 * against its question's own rules by App\Actions\Responses\EditResponseAnswer
 * (App\Support\AnswerValidationRules), since those rules depend on the
 * question's type/config and aren't expressible as a fixed rule set here.
 */
class UpdateResponseAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ->response is read unscoped rather than through the ordinary
        // relation — SurveyResponse's BelongsToOrganization global scope
        // would otherwise silently resolve to null for a different-org
        // actor (the row exists, it's just invisible to that scope),
        // crashing can('update', null) instead of correctly denying.
        $response = SurveyResponse::withoutGlobalScopes()->find($this->route('answer')->survey_response_id);

        return $response !== null && $this->user()->can('update', $response);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['present'],
        ];
    }
}
