<x-app-layout :title="__('surveys.responses.title')">
    @php
        $responsesI18n = [
            'load_failed' => __('surveys.public.validation_error'),
            'confirm_bulk_delete' => __('surveys.responses.confirm_bulk_delete'),
            'operator_equals' => __('surveys.responses.operator_equals'),
            'operator_not_equals' => __('surveys.responses.operator_not_equals'),
            'operator_gt' => __('surveys.responses.operator_gt'),
            'operator_gte' => __('surveys.responses.operator_gte'),
            'operator_lt' => __('surveys.responses.operator_lt'),
            'operator_lte' => __('surveys.responses.operator_lte'),
            'operator_contains' => __('surveys.responses.operator_contains'),
            'operator_is_answered' => __('surveys.responses.operator_is_answered'),
            'operator_is_not_answered' => __('surveys.responses.operator_is_not_answered'),
            'export_start_failed' => __('surveys.exports.start_failed'),
        ];
    @endphp
    <script>
        window.__i18n = @json($responsesI18n);
    </script>

    <div x-data="responseTable({{ $survey->id }})" x-init="init()" class="max-w-6xl mx-auto py-8 px-4 sm:px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('surveys.show', $survey) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    &larr; {{ __('surveys.responses.back_to_survey') }}
                </a>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $survey->title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.responses.title') }}</p>
            </div>

            @can('export', \App\Models\SurveyResponse::class)
                {{-- Export (spec §10.1) — reads this same component's own
                     filters/selected state, so "export filtered"/"export
                     selected" always matches what's on screen. --}}
                <div class="relative">
                    <button type="button" @click="toggleExportPanel()" class="rounded-control border border-border-subtle dark:border-gray-700 text-sm px-3 py-1.5 text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800">
                        {{ __('messages.actions.export') }}
                    </button>

                    <div
                        x-show="exportOpen"
                        x-cloak
                        @click.outside="exportOpen = false"
                        class="absolute right-0 mt-2 w-72 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-lg p-4 z-10"
                    >
                        <template x-if="!exportQueued">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.exports.scope') }}</label>
                                <select x-model="exportScope" class="w-full mb-3 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                    <option value="all">{{ __('surveys.exports.scope_all') }}</option>
                                    <option value="filtered">{{ __('surveys.exports.scope_filtered') }}</option>
                                    <option value="selected">{{ __('surveys.exports.scope_selected') }}</option>
                                </select>

                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.exports.format') }}</label>
                                <select x-model="exportFormat" class="w-full mb-3 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="csv">CSV</option>
                                </select>

                                <p x-show="exportSubmitError" x-text="exportSubmitError" x-cloak class="mb-2 text-sm text-red-600 dark:text-red-400"></p>

                                <button
                                    type="button"
                                    :disabled="exportSubmitting"
                                    @click="submitExport()"
                                    class="w-full rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3 py-1.5 transition"
                                >
                                    <span x-show="!exportSubmitting">{{ __('surveys.exports.submit') }}</span>
                                    <span x-show="exportSubmitting" x-cloak>{{ __('surveys.exports.submitting') }}</span>
                                </button>
                            </div>
                        </template>

                        <template x-if="exportQueued">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ __('surveys.exports.queued') }}</p>
                                <a href="{{ route('export-history.index') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">
                                    {{ __('surveys.exports.title') }} &rarr;
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            @endcan
        </div>

        {{-- Filters --}}
        <div class="mb-4 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.responses.filters') }}</h2>
                <div class="flex items-center gap-3 text-sm">
                    <button type="button" @click="addFilter()" class="text-brand-600 dark:text-brand-400 hover:underline">
                        + {{ __('surveys.responses.add_filter') }}
                    </button>
                    <button type="button" x-show="filters.length" @click="clearFilters()" class="text-gray-500 dark:text-gray-400 hover:underline">
                        {{ __('surveys.responses.clear_filters') }}
                    </button>
                </div>
            </div>

            <p x-show="filters.length === 0" class="text-sm text-gray-400 dark:text-gray-500">{{ __('surveys.responses.no_filters') }}</p>

            <div class="space-y-2">
                <template x-for="(filter, index) in filters" :key="index">
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
                            placeholder="{{ __('surveys.responses.value_placeholder') }}"
                            class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                        >

                        <button type="button" @click="removeFilter(index)" aria-label="{{ __('messages.actions.remove') }}" class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            <button
                type="button"
                x-show="filters.length"
                @click="applyFilters()"
                class="mt-3 rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition"
            >
                {{ __('surveys.responses.apply_filters') }}
            </button>
        </div>

        {{-- Column visibility --}}
        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
            <span class="text-gray-500 dark:text-gray-400">{{ __('surveys.responses.columns') }}:</span>
            <template x-for="question in questions" :key="question.id">
                <button
                    type="button"
                    @click="toggleColumn(question.id)"
                    class="px-2 py-1 rounded-control border transition"
                    :class="visibleColumns[question.id]
                        ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                        : 'border-border-subtle dark:border-gray-700 text-gray-500 dark:text-gray-400'"
                    x-text="question.label"
                ></button>
            </template>
        </div>

        {{-- Bulk actions. update/delete are per-instance SurveyResponsePolicy
             abilities (they take a SurveyResponse, not just a User), so
             there's no bare class-string @can for them the way viewAny/
             export have — checking the underlying permission directly
             instead, since neither ability adds anything beyond it plus an
             org match this page's own authorization already guarantees. --}}
        <div x-show="selected.size > 0" x-cloak class="mb-3 flex items-center gap-3 rounded-card bg-brand-50 dark:bg-brand-950 px-4 py-2 text-sm">
            <span class="text-brand-700 dark:text-brand-300" x-text="@js(__('surveys.responses.bulk_selected')).replace(':count', selected.size)"></span>
            @if (auth()->user()->can('response.edit'))
                <button type="button" :disabled="bulkWorking" @click="bulkReview('approved')" class="text-brand-700 dark:text-brand-300 hover:underline">{{ __('surveys.responses.bulk_approve') }}</button>
                <button type="button" :disabled="bulkWorking" @click="bulkReview('rejected')" class="text-brand-700 dark:text-brand-300 hover:underline">{{ __('surveys.responses.bulk_reject') }}</button>
                <button type="button" :disabled="bulkWorking" @click="bulkReview('archived')" class="text-brand-700 dark:text-brand-300 hover:underline">{{ __('surveys.responses.bulk_archive') }}</button>
            @endif
            @if (auth()->user()->can('response.delete'))
                <button type="button" :disabled="bulkWorking" @click="bulkDelete()" class="text-red-600 dark:text-red-400 hover:underline">{{ __('surveys.responses.bulk_delete') }}</button>
            @endif
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && responses.length === 0">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.responses.no_responses_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.responses.no_responses_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && responses.length > 0">
            <div class="overflow-x-auto border border-border-subtle dark:border-gray-800 rounded-card">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-subtle dark:bg-gray-900 text-left">
                        <tr>
                            <th class="px-3 py-2">
                                <input type="checkbox" :checked="selected.size === responses.length" @change="toggleSelectAll()">
                            </th>
                            <th class="px-3 py-2 cursor-pointer" @click="sortBy('submitted_at')">{{ __('surveys.responses.column_submitted_at') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.responses.column_status') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.responses.column_review_status') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.responses.column_collection_method') }}</th>
                            <template x-for="question in questions.filter(q => visibleColumns[q.id])" :key="question.id">
                                <th class="px-3 py-2" x-text="question.label"></th>
                            </template>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                        <template x-for="response in responses" :key="response.id">
                            <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800">
                                <td class="px-3 py-2">
                                    <input type="checkbox" :checked="selected.has(response.id)" @change="toggleSelect(response.id)">
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="response.submitted_at ? new Date(response.submitted_at).toLocaleString() : '—'"></td>
                                <td class="px-3 py-2">
                                    <span class="text-xs px-2 py-0.5 rounded-control bg-gray-100 dark:bg-gray-800" x-text="response.status"></span>
                                </td>
                                <td class="px-3 py-2">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-control"
                                        :class="{
                                            pending: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                            approved: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
                                            rejected: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                                            archived: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                        }[response.review_status]"
                                        x-text="response.review_status"
                                    ></span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="response.collection_method"></td>
                                <template x-for="question in questions.filter(q => visibleColumns[q.id])" :key="question.id">
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300 max-w-[200px] truncate" x-text="formatAnswer(answerFor(response, question.id))"></td>
                                </template>
                                <td class="px-3 py-2">
                                    <a :href="`/surveys/{{ $survey->id }}/responses/${response.id}`" class="text-brand-600 dark:text-brand-400 hover:underline">
                                        {{ __('surveys.responses.view') }}
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        <div x-show="meta && meta.last_page > 1" x-cloak class="mt-4 flex items-center justify-between text-sm">
            <button type="button" @click="goToPage(page - 1)" :disabled="page <= 1" class="text-gray-600 dark:text-gray-300 disabled:opacity-40">
                {{ __('messages.actions.back') }}
            </button>
            <span class="text-gray-500 dark:text-gray-400" x-text="meta ? `${meta.current_page} / ${meta.last_page}` : ''"></span>
            <button type="button" @click="goToPage(page + 1)" :disabled="meta && page >= meta.last_page" class="text-gray-600 dark:text-gray-300 disabled:opacity-40">
                {{ __('messages.actions.next') }}
            </button>
        </div>
    </div>
</x-app-layout>
