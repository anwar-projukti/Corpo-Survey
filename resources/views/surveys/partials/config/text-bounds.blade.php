<div class="space-y-2">
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.placeholder_text') }}</label>
        <input
            type="text"
            :value="question.config?.placeholder ?? ''"
            @blur="saveConfig({ placeholder: $event.target.value || undefined })"
            :disabled="!isDraftEditable"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.max_length') }}</label>
        <input
            type="number"
            :value="question.config?.maxLength ?? ''"
            @blur="saveConfig({ maxLength: $event.target.value === '' ? undefined : Number($event.target.value) })"
            :disabled="!isDraftEditable"
            class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1.5 text-sm disabled:opacity-70"
        >
    </div>
</div>
