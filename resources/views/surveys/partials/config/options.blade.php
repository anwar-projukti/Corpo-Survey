<div>
    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.builder.options') }}</label>

    <div class="mt-1 space-y-1">
        <template x-for="option in question.options" :key="option.id">
            <div class="flex items-center gap-1.5">
                <input
                    type="text"
                    :value="option.label"
                    @blur="updateOption(option, { label: $event.target.value })"
                    :disabled="!isDraftEditable"
                    placeholder="{{ __('surveys.builder.option_label_placeholder') }}"
                    class="flex-1 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-950 px-2 py-1 text-sm disabled:opacity-70"
                >
                <button
                    x-show="isDraftEditable"
                    type="button"
                    @click="deleteOption(question, option)"
                    class="text-gray-300 dark:text-gray-600 hover:text-red-500 shrink-0"
                    :title="@js(__('surveys.builder.delete_option'))"
                    aria-label="{{ __('surveys.builder.delete_option') }}"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    <button
        x-show="isDraftEditable"
        type="button"
        @click="addOption(question)"
        class="mt-2 text-sm text-brand-600 dark:text-brand-400 hover:underline"
    >
        + {{ __('surveys.builder.add_option') }}
    </button>
</div>
