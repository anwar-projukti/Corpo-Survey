<?php

namespace App\Http\Requests\Surveys;

use App\Enums\RuleAction;
use App\Support\ConditionTree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('rule')->version);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'conditions' => ['sometimes', 'array'],
            'action' => ['sometimes', Rule::enum(RuleAction::class)],
            'target' => ['sometimes', 'nullable', 'array'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('conditions') || $validator->errors()->has('action')) {
                return;
            }

            $rule = $this->route('rule');

            if ($this->has('conditions')) {
                $version = $rule->version;
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
            }

            $actionValue = $this->input('action') ?? $rule->action->value;
            $action = RuleAction::from($actionValue);
            $target = $this->has('target') ? $this->input('target') : $rule->target;

            if ($action->requiresTarget()) {
                $targetType = $action->targetType();

                if (! is_array($target) || ! isset($target["{$targetType}_id"])) {
                    $validator->errors()->add('target', "The \"{$action->value}\" action requires a target.{$targetType}_id.");
                }
            }
        });
    }
}
