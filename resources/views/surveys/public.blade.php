<x-public-survey-layout :title="$survey->title">
    @include('surveys.partials.public-i18n')

    <div x-data="publicSurveyForm({{ $survey->id }})" x-init="init()">
        <div x-show="loading" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('messages.system.loading') }}
        </div>

        <template x-if="!loading && (notFoundOrClosed || loadError)">
            <div class="py-16 text-center space-y-2">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.closed_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.public.closed_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && alreadyResponded">
            <div class="py-16 text-center space-y-2">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.duplicate_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.public.duplicate_body') }}</p>
            </div>
        </template>

        <template x-if="submitted">
            <div class="py-16 text-center space-y-2">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.public.thank_you_title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.public.thank_you_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && !notFoundOrClosed && !loadError && !alreadyResponded && !submitted">
            <div class="py-8 space-y-6">
                <div class="space-y-1">
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100" x-text="survey.title"></h1>
                    <p x-show="survey.description" x-text="survey.description" class="text-sm text-gray-600 dark:text-gray-400"></p>
                </div>

                @include('surveys.partials.public-otp-gate')

                <template x-if="!settings.require_otp || otpVerified">
                    <form @submit.prevent="isOnePerScreen ? (isLastQuestion ? submit() : next()) : submit()" class="space-y-6">
                        @include('surveys.partials.public-progress')

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
                </template>
            </div>
        </template>
    </div>
</x-public-survey-layout>
