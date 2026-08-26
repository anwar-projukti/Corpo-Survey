<x-app-layout :title="__('surveys.analytics.title')">
    @php
        $analyticsI18n = [
            'load_failed' => __('surveys.analytics.load_failed'),
            'response_trend' => __('surveys.analytics.response_trend'),
            'not_available' => __('surveys.analytics.not_available'),
        ];
    @endphp
    <script>
        window.__i18n = @json($analyticsI18n);
    </script>

    <div x-data="analyticsDashboard({{ $survey->id }})" x-init="init()" class="max-w-6xl mx-auto py-8 px-4 sm:px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('surveys.show', $survey) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    &larr; {{ __('surveys.analytics.back_to_survey') }}
                </a>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $survey->title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.title') }}</p>
            </div>

            <div class="hidden sm:flex items-center rounded-control border border-border-subtle dark:border-gray-700 p-0.5 text-xs">
                <template x-for="option in ['day', 'week', 'month']" :key="option">
                    <button
                        type="button"
                        @click="granularity = option"
                        class="px-2.5 py-1 rounded-control transition"
                        :class="granularity === option ? 'bg-brand-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                        x-text="{ day: @js(__('surveys.analytics.granularity_day')), week: @js(__('surveys.analytics.granularity_week')), month: @js(__('surveys.analytics.granularity_month')) }[option]"
                    ></button>
                </template>
            </div>
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && !hasResponses">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.analytics.no_responses_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.no_responses_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && !loadError && hasResponses">
            <div class="space-y-6">
                {{-- Overview cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.total_responses') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="data.overview.total"></p>
                    </div>
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.complete_responses') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="data.overview.complete"></p>
                    </div>
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.partial_responses') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="data.overview.partial"></p>
                    </div>
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.completion_rate') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="`${data.overview.completion_rate}%`"></p>
                    </div>
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.avg_completion_time') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="formatSeconds(data.overview.avg_completion_seconds)"></p>
                    </div>
                </div>

                {{-- Response trend --}}
                <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.analytics.response_trend') }}</h2>
                    <div class="h-64">
                        <canvas x-ref="trendCanvas"></canvas>
                    </div>
                </div>

                {{-- Drop-off --}}
                <div x-show="data.drop_off.length" class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.analytics.drop_off') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2">{{ __('surveys.analytics.drop_off_column_question') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.analytics.drop_off_column_answered') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.analytics.drop_off_column_rate') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                                <template x-for="row in data.drop_off" :key="row.question_id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="row.label"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="row.answered"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="`${row.drop_off_rate}%`"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Per-question breakdown --}}
                <div>
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.analytics.question_breakdown') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <template x-for="question in chartableQuestions" :key="question.question_id">
                            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3" x-text="question.label"></p>
                                <div class="h-56">
                                    <canvas :data-question-id="question.question_id" class="analytics-question-canvas"></canvas>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
