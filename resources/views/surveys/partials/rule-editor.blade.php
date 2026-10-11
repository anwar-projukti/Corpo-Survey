<div
    x-data="{
        get questions() { return allQuestions(); },
        get targetType() {
            return ['skip', 'go_to_section'].includes(rule.action) ? 'section' : (rule.action === 'end_survey' ? null : 'question');
        },
        setCondition(patch) {
            updateRule(rule, { conditions: { ...rule.conditions, ...patch } });
        },
        setAction(action) {
            updateRule(rule, { action, target: null });
        },
        setTarget(id) {
            const key = this.targetType === 'section' ? 'section_id' : 'question_id';
            updateRule(rule, { target: { [key]: Number(id) } });
        },
        needsValue() {
            return !['is_answered', 'is_not_answered'].includes(rule.conditions?.operator);
        },
    }"
    class="space-y-2 text-sm"
>
    <div class="flex items-center justify-between">
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('surveys.builder.rule_if') }}</span>
        <button x-show="isDraftEditable" type="button" @click="deleteRule(rule)" aria-label="{{ __('messages.actions.delete') }}" class="text-gray-300 dark:text-gray-600 hover:text-red-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-2">
        <select
            :value="rule.conditions?.question_id"
            @change="setCondition({ question_id: Number($event.target.value) })"
            :disabled="!isDraftEditable"
            class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
            <option value="" disabled>{{ __('surveys.builder.select_question') }}</option>
            <template x-for="q in questions" :key="q.id">
                <option :value="q.id" x-text="q.label" :selected="q.id === rule.conditions?.question_id"></option>
            </template>
        </select>

        <select
            :value="rule.conditions?.operator"
            @change="setCondition({ operator: $event.target.value })"
            :disabled="!isDraftEditable"
            class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
            @foreach (['equals', 'not_equals', 'gt', 'gte', 'lt', 'lte', 'contains', 'is_answered', 'is_not_answered'] as $operator)
                <option value="{{ $operator }}">{{ __('surveys.builder.rule_operator.'.$operator) }}</option>
            @endforeach
        </select>

        <input
            x-show="needsValue()"
            type="text"
            :value="rule.conditions?.value ?? ''"
            @blur="setCondition({ value: $event.target.value })"
            :disabled="!isDraftEditable"
            placeholder="{{ __('surveys.builder.value') }}"
            class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
    </div>

    <span class="block text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 pt-1">{{ __('surveys.builder.rule_then') }}</span>

    <select
        :value="rule.action"
        @change="setAction($event.target.value)"
        :disabled="!isDraftEditable"
        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
    >
        @foreach (['show', 'hide', 'require', 'make_optional', 'skip', 'go_to_section', 'end_survey'] as $action)
            <option value="{{ $action }}">{{ __('surveys.builder.rule_action.'.$action) }}</option>
        @endforeach
    </select>

    <select
        x-show="targetType === 'question'"
        :value="rule.target?.question_id"
        @change="setTarget($event.target.value)"
        :disabled="!isDraftEditable"
        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
    >
        <option value="" disabled>{{ __('surveys.builder.select_question') }}</option>
        <template x-for="q in questions" :key="q.id">
            <option :value="q.id" x-text="q.label" :selected="q.id === rule.target?.question_id"></option>
        </template>
    </select>

    <select
        x-show="targetType === 'section'"
        :value="rule.target?.section_id"
        @change="setTarget($event.target.value)"
        :disabled="!isDraftEditable"
        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
    >
        <option value="" disabled>{{ __('surveys.builder.select_section') }}</option>
        <template x-for="s in version.sections" :key="s.id">
            <option :value="s.id" x-text="s.title" :selected="s.id === rule.target?.section_id"></option>
        </template>
    </select>
</div>
