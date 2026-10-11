<div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
    <button
        type="button"
        @click="showRules = !showRules"
        class="w-full flex items-center justify-between px-4 py-3"
    >
        <span class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.builder.rules') }}</span>
        <svg class="h-4 w-4 text-gray-400 transition" :class="showRules ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="showRules" x-cloak x-transition class="border-t border-border-subtle dark:border-gray-800 p-4 space-y-3">
        <p x-show="version.rules.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('surveys.builder.no_rules') }}
        </p>

        <template x-for="rule in version.rules" :key="rule.id">
            <div class="rounded-control border border-border-subtle dark:border-gray-700 p-3">
                @include('surveys.partials.rule-editor')
            </div>
        </template>

        <button
            x-show="isDraftEditable"
            type="button"
            @click="addRule()"
            class="text-sm text-brand-600 dark:text-brand-400 hover:underline"
        >
            + {{ __('surveys.builder.add_rule') }}
        </button>
    </div>
</div>
