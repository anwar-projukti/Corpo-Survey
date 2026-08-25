<div class="grid grid-cols-2 gap-2">
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.min_value') }}</label>
        <input
            type="number"
            :value="question.config?.min ?? ''"
            @blur="saveConfig({ min: $event.target.value === '' ? undefined : Number($event.target.value) })"
            :disabled="!isDraftEditable"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.max_value') }}</label>
        <input
            type="number"
            :value="question.config?.max ?? ''"
            @blur="saveConfig({ max: $event.target.value === '' ? undefined : Number($event.target.value) })"
            :disabled="!isDraftEditable"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
    </div>
</div>
