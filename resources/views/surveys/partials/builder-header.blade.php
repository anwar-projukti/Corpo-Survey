<header class="shrink-0 border-b border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 px-4 sm:px-6 py-3">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('surveys.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>

            <input
                type="text"
                :value="survey.title"
                @blur="axios.patch(`/api/v1/surveys/${surveyId}`, { title: $event.target.value }).then(r => survey.title = r.data.title)"
                :disabled="!isDraftEditable"
                class="font-semibold text-gray-900 dark:text-gray-100 bg-transparent border-none focus:ring-1 focus:ring-brand-500 rounded-control px-1 -mx-1 disabled:opacity-70 truncate"
            >
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <div class="hidden sm:flex items-center rounded-control border border-border-subtle dark:border-gray-700 p-0.5 text-xs">
                <button
                    type="button"
                    @click="viewVersion('draft')"
                    x-show="survey.current_draft_version"
                    class="px-2.5 py-1 rounded-control transition"
                    :class="mode === 'draft' ? 'bg-brand-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                >
                    {{ __('surveys.builder.draft_badge') }}
                </button>
                <button
                    type="button"
                    @click="viewVersion('published')"
                    x-show="survey.current_published_version"
                    class="px-2.5 py-1 rounded-control transition"
                    :class="mode === 'published' ? 'bg-brand-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                >
                    {{ __('surveys.builder.published_badge') }}
                </button>
            </div>

            <span x-show="saving" class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.builder.saving') }}</span>

            <a
                :href="`/surveys/${surveyId}/responses`"
                class="rounded-control border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 text-sm font-medium px-3 py-1.5 transition"
            >
                {{ __('surveys.builder.responses') }}
            </a>

            <a
                :href="`/surveys/${surveyId}/analytics`"
                class="rounded-control border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 text-sm font-medium px-3 py-1.5 transition"
            >
                {{ __('surveys.builder.analytics') }}
            </a>

            @can('assign', $survey)
                <button
                    type="button"
                    @click="openAssignments()"
                    class="rounded-control border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 text-sm font-medium px-3 py-1.5 transition"
                >
                    {{ __('surveys.builder.manage_field_users') }}
                </button>
            @endcan

            <button
                type="button"
                x-show="survey.current_published_version"
                @click="viewPublicLink()"
                class="inline-flex items-center gap-1.5 rounded-control border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 text-sm font-medium px-3 py-1.5 transition"
                title="{{ __('surveys.builder.view_public_survey') }}"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span class="hidden sm:inline">{{ __('surveys.builder.view_public_survey') }}</span>
            </button>

            <button
                type="button"
                x-show="survey.current_published_version"
                @click="copyPublicLink()"
                class="rounded-control border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 text-sm font-medium px-3 py-1.5 transition"
                x-text="publicLinkCopied ? @js(__('surveys.builder.public_link_copied')) : @js(__('surveys.builder.copy_public_link'))"
            ></button>

            @can('update', $survey)
                <button
                    type="button"
                    x-show="!survey.current_draft_version"
                    @click="startNewDraft()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition"
                >
                    {{ __('surveys.builder.start_new_draft') }}
                </button>
            @endcan

            @can('publish', $survey)
                <button
                    type="button"
                    x-show="isDraftEditable"
                    @click="confirmPublish()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition"
                >
                    {{ __('surveys.builder.publish') }}
                </button>
            @endcan
        </div>
    </div>
</header>
