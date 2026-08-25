<?php

namespace App\Http\Requests\Surveys;

use App\Data\QuestionConfigData;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
        $rules = [
            'type' => ['sometimes', 'required', Rule::enum(QuestionType::class)],
            'label' => ['sometimes', 'required', 'string', 'max:1000'],
            'help_text' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_required' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
        ];

        // Validate config against whichever type applies after this
        // request — the new type if the client is changing it, otherwise
        // the question's current type.
        $type = QuestionType::tryFrom($this->input('type'))
            ?? $this->route('question')->type;

        if ($type instanceof QuestionType) {
            foreach (QuestionConfigData::rulesForType($type) as $field => $fieldRules) {
                $rules["config.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }
}
