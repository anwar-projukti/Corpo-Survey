<x-app-layout :title="$survey->title">
    @include('surveys.partials.public-i18n')
    @php
        $fieldI18n = [
            'load_failed' => __('surveys.field.load_failed'),
            'generic_error' => __('surveys.field.generic_error'),
            'queue_failed' => __('surveys.field.queue_failed'),
        ];
    @endphp
    <script>
        window.__i18n = { ...window.__i18n, ...@json($fieldI18n) };
    </script>

    <div x-data="fieldSurveyForm({{ $survey->id }})" x-init="init()" class="max-w-xl mx-auto py-8 px-4 sm:px-6">
        <a href="{{ route('my.surveys.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 mb-4">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            {{ __('surveys.field.back_to_my_surveys') }}
        </a>

        <div x-show="!online" x-cloak class="mb-4 rounded-card bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-sm px-4 py-3">
            {{ __('surveys.field.offline_banner') }}
        </div>

        <div x-show="loading" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('messages.system.loading') }}
        </div>

        <div x-show="!loading && loadError" x-cloak class="py-16 text-center text-sm text-red-600 dark:text-red-400" x-text="loadError"></div>

        <template x-if="submitted && !queuedOffline">
            <div class="py-16 text-center space-y-4">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.field.submitted_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.field.submitted_body') }}</p>
                <button
                    type="button"
                    @click="startAnother()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 transition"
                >
                    {{ __('surveys.field.submit_another') }}
                </button>
            </div>
        </template>

        <template x-if="submitted && queuedOffline">
            <div class="py-16 text-center space-y-4">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.field.queued_offline_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.field.queued_offline_body') }}</p>
                <button
                    type="button"
                    @click="startAnother()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 transition"
                >
                    {{ __('surveys.field.submit_another') }}
                </button>
            </div>
        </template>

        <template x-if="!loading && !loadError && !submitted && survey">
            <div class="space-y-6">
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100" x-text="survey.title"></h1>
                    <p x-show="survey.description" x-text="survey.description" class="text-sm text-gray-600 dark:text-gray-400"></p>
                </div>

                <form @submit.prevent="isOnePerScreen ? (isLastQuestion ? submit() : next()) : submit()" class="space-y-6">
                    <template x-if="settings.show_progress_bar && isOnePerScreen && visibleQuestions.length">
                        <div class="space-y-1">
                            <div class="h-1.5 rounded-full bg-surface-subtle dark:bg-gray-800 overflow-hidden">
                                <div class="h-full bg-brand-600 transition-all" :style="`width: ${progress.percent}%`"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="@js(__('surveys.public.progress'))
                                .replace(':current', progress.current)
                                .replace(':total', progress.total)"></p>
                        </div>
                    </template>

                    <template x-if="isOnePerScreen">
                        <div>
                            <template x-if="currentQuestion">
                                <div x-data="{ get question() { return currentQuestion } }">
                                    @include('surveys.partials.public-question')
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!isOnePerScreen">
                        <div class="space-y-8">
                            <template x-for="question in visibleQuestions" :key="question.id">
                                @include('surveys.partials.public-question')
                            </template>
                        </div>
                    </template>

                    <p x-show="submitError" x-text="submitError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

                    <div class="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            x-show="isOnePerScreen && currentIndex > 0"
                            @click="previous()"
                            class="h-10 px-4 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800"
                        >
                            {{ __('surveys.public.previous_button') }}
                        </button>

                        <button
                            type="submit"
                            :disabled="submitting"
                            class="ml-auto h-10 px-5 rounded-control bg-brand-600 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                            x-text="isOnePerScreen && !isLastQuestion ? @js(__('surveys.public.next_button')) : @js(__('surveys.public.submit_button'))"
                        ></button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</x-app-layout>
