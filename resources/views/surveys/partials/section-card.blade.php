<div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
    <div class="flex items-start gap-2 px-4 py-3 border-b border-border-subtle dark:border-gray-800">
        <span
            x-show="isDraftEditable"
            class="cursor-move text-gray-300 dark:text-gray-600 mt-1.5"
            title="{{ __('messages.actions.edit') }}"
        >
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm6-10a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2z"/></svg>
        </span>

        <div class="flex-1 min-w-0">
            <input
                type="text"
                :value="section.title"
                @blur="updateSection(section, { title: $event.target.value })"
                :disabled="!isDraftEditable"
                placeholder="{{ __('surveys.builder.section_title') }}"
                class="w-full font-medium text-gray-900 dark:text-gray-100 bg-transparent border-none focus:ring-1 focus:ring-brand-500 rounded-control px-1 -mx-1 disabled:opacity-70"
            >
            <input
                type="text"
                :value="section.description"
                @blur="updateSection(section, { description: $event.target.value })"
                :disabled="!isDraftEditable"
                placeholder="{{ __('surveys.builder.section_description') }}"
                class="w-full mt-1 text-sm text-gray-500 dark:text-gray-400 bg-transparent border-none focus:ring-1 focus:ring-brand-500 rounded-control px-1 -mx-1 disabled:opacity-70"
            >
        </div>

        <button
            x-show="isDraftEditable"
            type="button"
            @click="confirmDeleteSection(section)"
            class="text-gray-300 dark:text-gray-600 hover:text-red-500 shrink-0"
            :title="@js(__('surveys.builder.delete_section'))"
            aria-label="{{ __('surveys.builder.delete_section') }}"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="p-3 space-y-2">
        <p x-show="section.questions.length === 0" class="text-sm text-gray-400 dark:text-gray-500 px-2 py-1">
            {{ __('surveys.builder.no_questions') }}
        </p>

        <div
            x-sortable="(ids) => reorderQuestions(section, ids)"
            :class="isDraftEditable ? '' : 'pointer-events-none'"
            class="space-y-2"
        >
            <template x-for="question in section.questions" :key="question.id">
                <div x-sortable-item="question.id">
                    @include('surveys.partials.question-row')
                </div>
            </template>
        </div>

        <div x-show="isDraftEditable" x-data="{ open: false }" class="relative">
            <button
                type="button"
                @click="open = !open"
                @click.outside="open = false"
                class="w-full text-left rounded-control px-2 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
            >
                + {{ __('surveys.builder.add_question') }}
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition
                class="absolute left-0 mt-1 w-72 max-h-80 overflow-y-auto rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 shadow-lg z-20 p-2"
            >
                @include('surveys.partials.question-type-picker')
            </div>
        </div>
    </div>
</div>
