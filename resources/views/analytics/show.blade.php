<x-app-layout :title="__('surveys.analytics.title')">
    @php
        $analyticsI18n = [
            'load_failed' => __('surveys.analytics.load_failed'),
            'response_trend' => __('surveys.analytics.response_trend'),
            'not_available' => __('surveys.analytics.not_available'),
            'reports_load_failed' => __('surveys.analytics.reports_load_failed'),
            'reports_save_failed' => __('surveys.analytics.reports_save_failed'),
            'reports_run_failed' => __('surveys.analytics.reports_run_failed'),
            'reports_confirm_delete' => __('surveys.analytics.reports_confirm_delete'),
            'operator_equals' => __('surveys.responses.operator_equals'),
            'operator_not_equals' => __('surveys.responses.operator_not_equals'),
            'operator_gt' => __('surveys.responses.operator_gt'),
            'operator_gte' => __('surveys.responses.operator_gte'),
            'operator_lt' => __('surveys.responses.operator_lt'),
            'operator_lte' => __('surveys.responses.operator_lte'),
            'operator_contains' => __('surveys.responses.operator_contains'),
            'operator_is_answered' => __('surveys.responses.operator_is_answered'),
            'operator_is_not_answered' => __('surveys.responses.operator_is_not_answered'),
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

    <div x-data="reportBuilder({{ $survey->id }})" x-init="init()" class="max-w-6xl mx-auto pb-12 px-4 sm:px-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.analytics.reports_title') }}</h2>
            <button
                type="button"
                @click="openNewForm()"
                class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition"
            >
                {{ __('surveys.analytics.reports_new') }}
            </button>
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && reports.length === 0 && !showForm">
            <div class="text-center py-12 rounded-card border border-dashed border-border-subtle dark:border-gray-700">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.analytics.reports_none_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.reports_none_body') }}</p>
            </div>
        </template>

        {{-- Report builder form --}}
        <div x-show="showForm" x-cloak class="mb-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
            <p x-show="saveError" x-text="saveError" x-cloak class="mb-3 text-sm text-red-600 dark:text-red-400"></p>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.analytics.reports_name') }}</label>
                <input
                    type="text"
                    x-model="form.name"
                    placeholder="{{ __('surveys.analytics.reports_name_placeholder') }}"
                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-3 py-1.5"
                >
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.analytics.reports_select_questions') }}</label>
                <div class="space-y-1">
                    <template x-for="question in questions" :key="question.id">
                        <div class="flex items-center justify-between gap-3 py-1">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" :checked="form.question_ids.includes(question.id)" @change="toggleQuestion(question.id)">
                                <span x-text="question.label"></span>
                            </label>
                            <select
                                x-show="form.question_ids.includes(question.id)"
                                x-model="form.chart_types[question.id]"
                                class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-xs px-2 py-1"
                            >
                                <option value="doughnut">{{ __('surveys.analytics.reports_chart_doughnut') }}</option>
                                <option value="bar">{{ __('surveys.analytics.reports_chart_bar') }}</option>
                            </select>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.analytics.reports_filters') }}</label>
                    <button type="button" @click="addFilter()" class="text-xs text-brand-600 dark:text-brand-400 hover:underline">
                        + {{ __('surveys.analytics.reports_add_filter') }}
                    </button>
                </div>
                <div class="space-y-2">
                    <template x-for="(filter, index) in form.filters" :key="index">
                        <div class="flex flex-wrap items-center gap-2">
                            <select x-model.number="filter.question_id" class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <template x-for="question in questions" :key="question.id">
                                    <option :value="question.id" x-text="question.label"></option>
                                </template>
                            </select>

                            <select x-model="filter.operator" class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <template x-for="operator in operators" :key="operator">
                                    <option :value="operator" x-text="operatorLabels[operator]"></option>
                                </template>
                            </select>

                            <input
                                type="text"
                                x-show="needsValue(filter.operator)"
                                x-model="filter.value"
                                class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                            >

                            <button type="button" @click="removeFilter(index)" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-3 max-w-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.analytics.reports_date_from') }}</label>
                    <input type="date" x-model="form.date_from" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.analytics.reports_date_to') }}</label>
                    <input type="date" x-model="form.date_to" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    :disabled="saving || form.question_ids.length === 0 || !form.name"
                    @click="saveReport()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition disabled:opacity-50"
                    x-text="saving ? @js(__('surveys.analytics.reports_saving')) : @js(__('surveys.analytics.reports_save'))"
                ></button>
                <button type="button" @click="closeForm()" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">
                    {{ __('surveys.analytics.reports_cancel') }}
                </button>
            </div>
        </div>

        {{-- Saved report list --}}
        <div class="space-y-3">
            <template x-for="report in reports" :key="report.id">
                <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="report.name"></p>
                        <div class="flex items-center gap-3 text-sm shrink-0">
                            <button type="button" @click="runReport(report)" class="text-brand-600 dark:text-brand-400 hover:underline">
                                {{ __('surveys.analytics.reports_run') }}
                            </button>
                            <button type="button" @click="openEditForm(report)" class="text-gray-600 dark:text-gray-300 hover:underline">
                                {{ __('surveys.analytics.reports_edit') }}
                            </button>
                            <button type="button" @click="deleteReport(report)" class="text-red-600 dark:text-red-400 hover:underline">
                                {{ __('surveys.analytics.reports_delete') }}
                            </button>
                        </div>
                    </div>

                    {{-- Run result, inline under the report that produced it --}}
                    <div x-show="activeReportId === report.id" x-cloak class="mt-4 border-t border-border-subtle dark:border-gray-800 pt-4">
                        <div x-show="runLoading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
                        <p x-show="runError" x-text="runError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

                        <template x-if="!runLoading && !runError && runResult">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="@js(__('surveys.analytics.reports_response_count')).replace(':count', runResult.response_count)"></p>
                                    <button type="button" @click="closeRun()" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                                        {{ __('surveys.analytics.reports_close') }}
                                    </button>
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <template x-for="question in runResult.questions" :key="question.question_id">
                                        <div class="rounded-card border border-border-subtle dark:border-gray-800 p-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2" x-text="question.label"></p>
                                            <div class="h-48">
                                                <canvas x-init="renderRunChart($el, question)"></canvas>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
