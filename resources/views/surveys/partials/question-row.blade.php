<div
    @click="activeQuestionId = question.id"
    class="flex items-center gap-2 rounded-control px-2 py-2 cursor-pointer transition"
    :class="activeQuestionId === question.id
        ? 'bg-brand-50 dark:bg-brand-950 ring-1 ring-brand-300 dark:ring-brand-700'
        : 'hover:bg-surface-subtle dark:hover:bg-gray-800'"
>
    <span
        x-show="isDraftEditable"
        class="cursor-move text-gray-300 dark:text-gray-600 shrink-0"
        @click.stop
    >
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm6-10a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2z"/></svg>
    </span>

    <span class="shrink-0 text-[10px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 w-24 truncate" x-text="question.type"></span>

    <span class="flex-1 truncate text-sm text-gray-900 dark:text-gray-100" x-text="question.label"></span>

    <span x-show="question.is_required" class="text-red-500 shrink-0" title="{{ __('surveys.builder.required') }}">*</span>

    <button
        x-show="isDraftEditable"
        type="button"
        @click.stop="deleteQuestion(section, question)"
        class="text-gray-300 dark:text-gray-600 hover:text-red-500 shrink-0"
        :title="@js(__('surveys.builder.delete_question'))"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>
</div>
