<?php

namespace App\Http\Requests\Surveys;

use App\Enums\RuleAction;
use App\Support\ConditionTree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('version'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'conditions' => ['required', 'array'],
            'action' => ['required', Rule::enum(RuleAction::class)],
            'target' => ['nullable', 'array'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Validates the recursive conditions tree — not expressible as
     * declarative rule strings, see App\Support\ConditionTree — and cross
     * -checks the action/target pairing, after the rules() above have
     * already confirmed both fields are present and well-typed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('conditions') || $validator->errors()->has('action')) {
                return;
            }

            $version = $this->route('version');
            $validQuestionIds = $version->sections()
                ->with('questions:id,survey_section_id')
                ->get()
                ->flatMap->questions
                ->pluck('id')
                ->all();

            try {
                ConditionTree::validate($this->input('conditions'), $validQuestionIds);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            $action = RuleAction::from($this->input('action'));

            if ($action->requiresTarget() && ! $this->filled('target')) {
                $validator->errors()->add('target', "The \"{$action->value}\" action requires a target.");
            }

            if ($action->requiresTarget() && $this->filled('target')) {
                $targetType = $action->targetType();
                $target = $this->input('target');

                if (! isset($target["{$targetType}_id"])) {
                    $validator->errors()->add('target', "The \"{$action->value}\" action requires a target.{$targetType}_id.");
                }
            }
        });
    }
}
