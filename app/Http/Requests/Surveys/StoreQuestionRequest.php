<?php

namespace App\Http\Requests\Surveys;

use App\Data\QuestionConfigData;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('section')->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'label' => ['required', 'string', 'max:1000'],
            'help_text' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
        ];

        $type = QuestionType::tryFrom($this->input('type'));

        if ($type) {
            foreach (QuestionConfigData::rulesForType($type) as $field => $fieldRules) {
                $rules["config.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }
}
