<x-public-survey-layout :title="$survey->title">
    @include('surveys.partials.public-i18n')

    <div x-data="publicSurveyForm({{ $survey->id }})" x-init="init()">
        <div x-show="loading" class="flex items-center justify-center gap-2.5 py-24 text-sm text-gray-500 dark:text-gray-400">
            <svg class="h-4 w-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9V0c6.627 0 12 5.373 12 12h-3Z"></path>
            </svg>
            {{ __('messages.system.loading') }}
        </div>

        <template x-if="!loading && (notFoundOrClosed || loadError)">
            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm p-8 sm:p-10 text-center space-y-3">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.closed_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">{{ __('surveys.public.closed_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && alreadyResponded">
            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm p-8 sm:p-10 text-center space-y-3">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.duplicate_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">{{ __('surveys.public.duplicate_body') }}</p>
            </div>
        </template>

        <template x-if="submitted">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm p-8 sm:p-10 text-center space-y-3"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50 dark:bg-green-950 text-green-600 dark:text-green-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.thank_you_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">{{ __('surveys.public.thank_you_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && !notFoundOrClosed && !loadError && !alreadyResponded && !submitted">
            <div class="space-y-5">
                {{-- Survey title card — sits above the form card as its own
                     block, echoing how the admin builder header separates
                     "what this is" from "the thing you're filling out". --}}
                <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm px-6 py-5 sm:px-8 sm:py-6">
                    <div class="flex items-start gap-3.5">
                        <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75M3.75 4.5h16.5A1.5 1.5 0 0 1 21.75 6v13.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 9h19.5" />
                            </svg>
                        </div>
                        <div class="min-w-0 pt-0.5">
                            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100 text-balance" x-text="survey.title"></h1>
                            <p x-show="survey.description" x-text="survey.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                        </div>
                    </div>
                </div>

                @include('surveys.partials.public-otp-gate')

                <template x-if="!settings.require_otp || otpVerified">
                    <form @submit.prevent="isOnePerScreen ? (isLastQuestion ? submit() : next()) : submit()">
                        <div class="mb-3">
                            @include('surveys.partials.public-progress')
                        </div>

                        <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm p-6 sm:p-8">
                            <template x-if="isOnePerScreen">
                                <div
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-x-2"
                                    x-transition:enter-end="opacity-100 translate-x-0"
                                    :key="currentIndex"
                                >
                                    <template x-if="currentQuestion">
                                        <div x-data="{ get question() { return currentQuestion } }">
                                            @include('surveys.partials.public-question')
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!isOnePerScreen">
                                <div class="space-y-9" x-data="{
                                    get visibleIds() { return new Set(visibleQuestions.map(q => q.id)); },
                                    get sectionsWithVisibleQuestions() {
                                        return survey.version.sections
                                            .map((section, i) => ({ ...section, number: i + 1, questions: section.questions.filter(q => this.visibleIds.has(q.id)) }))
                                            .filter(section => section.questions.length > 0);
                                    },
                                }">
                                    <template x-for="(section, sIndex) in sectionsWithVisibleQuestions" :key="section.id">
                                        <div class="space-y-5" :class="sIndex > 0 ? 'pt-9 border-t border-border-subtle dark:border-gray-800' : ''">
                                            <div x-show="section.title || section.description" class="flex items-start gap-3">
                                                <span
                                                    x-show="section.title"
                                                    x-text="section.number"
                                                    class="shrink-0 mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400 text-xs font-semibold"
                                                ></span>
                                                <div class="min-w-0">
                                                    <h2 x-show="section.title" x-text="section.title" class="font-semibold text-gray-900 dark:text-gray-100"></h2>
                                                    <p x-show="section.description" x-text="section.description" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"></p>
                                                </div>
                                            </div>

                                            <div class="space-y-7">
                                                <template x-for="question in section.questions" :key="question.id">
                                                    <div x-data="{ get questionIndex() { return visibleQuestions.findIndex(q => q.id === question.id) } }">
                                                        @include('surveys.partials.public-question')
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <p x-show="submitError" x-cloak class="mt-4 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span x-text="submitError"></span>
                        </p>

                        <div class="mt-5 flex items-center justify-between gap-3">
                            <button
                                type="button"
                                x-show="isOnePerScreen && currentIndex > 0"
                                @click="previous()"
                                class="h-11 px-5 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 bg-surface dark:bg-gray-900 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                            >
                                {{ __('surveys.public.previous_button') }}
                            </button>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="ml-auto inline-flex items-center gap-2 h-11 px-6 rounded-control bg-brand-600 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 hover:shadow-md disabled:opacity-60 disabled:hover:shadow-sm transition"
                            >
                                <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9V0c6.627 0 12 5.373 12 12h-3Z"></path>
                                </svg>
                                <span x-text="isOnePerScreen && !isLastQuestion ? @js(__('surveys.public.next_button')) : @js(__('surveys.public.submit_button'))"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </template>
    </div>
</x-public-survey-layout>
