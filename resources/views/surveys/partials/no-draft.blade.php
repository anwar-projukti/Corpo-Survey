<div class="max-w-md mx-auto text-center py-20">
    <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.builder.no_draft_title') }}</p>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.no_draft_body') }}</p>

    <button
        type="button"
        @click="startNewDraft()"
        x-show="mode === 'published'"
        class="mt-4 rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 transition"
    >
        {{ __('surveys.builder.start_new_draft') }}
    </button>
</div>
