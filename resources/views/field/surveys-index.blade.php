<x-app-layout :title="__('surveys.field.my_surveys')">
    @php
        $fieldI18n = [
            'validation_error' => __('surveys.public.validation_error'),
            'load_failed' => __('surveys.field.load_failed'),
            'generic_error' => __('surveys.field.generic_error'),
            'queued_count_singular' => trans_choice('surveys.field.queued_count', 1, ['count' => ':count']),
            'queued_count_plural' => trans_choice('surveys.field.queued_count', 2, ['count' => ':count']),
        ];
    @endphp
    <script>
        window.__i18n = @json($fieldI18n);
    </script>

    <div
        x-data="{
            ...fieldSurveysIndex(),
            queuedCountLabel(count) {
                const template = count === 1 ? window.__i18n.queued_count_singular : window.__i18n.queued_count_plural;
                return template.replace(':count', count);
            },
        }"
        x-init="init()"
        class="max-w-4xl mx-auto py-10 px-4 sm:px-6"
    >
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-600 dark:text-brand-400">{{ now()->translatedFormat('l, j F Y') }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
                    {{ __('surveys.field.my_surveys') }}
                </h1>
            </div>

            <span
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                :class="online
                    ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                    : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
            >
                <span class="h-1.5 w-1.5 rounded-full" :class="online ? 'bg-green-500' : 'bg-amber-500'"></span>
                <span x-text="online ? @js(__('surveys.field.online')) : @js(__('surveys.field.offline'))"></span>
            </span>
        </div>

        <div x-show="!online" x-cloak class="mt-4 rounded-card bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-sm px-4 py-3 flex items-center justify-between gap-3">
            <span>{{ __('surveys.field.offline_banner') }}</span>
            <button type="button" @click="recheckConnection()" class="shrink-0 font-medium underline hover:no-underline">
                {{ __('surveys.field.retry_connection') }}
            </button>
        </div>

        <div x-show="queueCount > 0" x-cloak class="mt-4 flex items-center justify-between rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-4 py-3">
            <span
                class="text-sm text-gray-700 dark:text-gray-300"
                x-text="syncing ? @js(__('surveys.field.syncing')) : queuedCountLabel(queueCount)"
            ></span>
            <button
                type="button"
                @click="syncNow()"
                :disabled="syncing || !online"
                class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline disabled:opacity-50"
            >
                {{ __('surveys.field.sync_now') }}
            </button>
        </div>

        <div x-show="stats" x-cloak class="mt-6 grid grid-cols-3 gap-4">
            <x-dashboard.stat-tile :label="__('surveys.field.today_count')" value-text="stats?.today" color="brand">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5m3.75-3.75h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </x-dashboard.stat-tile>
            <x-dashboard.stat-tile :label="__('surveys.field.week_count')" value-text="stats?.this_week" color="amber">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </x-dashboard.stat-tile>
            <x-dashboard.stat-tile :label="__('surveys.field.total_count')" value-text="stats?.total" color="emerald">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </x-dashboard.stat-tile>
        </div>

        {{-- Tabs --}}
        <div class="mt-8 flex items-center gap-1 border-b border-border-subtle dark:border-gray-800">
            <button
                type="button"
                @click="switchTab('surveys')"
                :class="activeTab === 'surveys'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="border-b-2 px-3 pb-2.5 text-sm font-medium transition -mb-px"
            >
                {{ __('surveys.field.tab_surveys') }}
            </button>
            <button
                type="button"
                @click="switchTab('responses')"
                :class="activeTab === 'responses'
                    ? 'border-brand-600 text-brand-600 dark:border-brand-400 dark:text-brand-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                class="border-b-2 px-3 pb-2.5 text-sm font-medium transition -mb-px"
            >
                {{ __('surveys.field.tab_responses') }}
            </button>
        </div>

        {{-- Surveys tab --}}
        <div x-show="activeTab === 'surveys'">
            <div x-show="loading" class="mt-8 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('messages.system.loading') }}
            </div>

            <template x-if="!loading && surveys.length === 0">
                <div class="mt-8 rounded-card border border-dashed border-border-subtle dark:border-gray-700 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6a1.5 1.5 0 0 0-1.5 1.5v16.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06l-3.622-3.622a1.5 1.5 0 0 0-1.06-.44Z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.field.no_assigned_surveys_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.field.no_assigned_surveys_body') }}</p>
                </div>
            </template>

            <div x-show="!loading && surveys.length > 0" class="mt-6 space-y-3">
                <template x-for="survey in surveys" :key="survey.id">
                    <a
                        :href="`/my/surveys/${survey.id}`"
                        class="group flex items-center gap-4 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4 transition hover:-translate-y-0.5 hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-lg hover:shadow-gray-900/5"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6a1.5 1.5 0 0 0-1.5 1.5v16.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06l-3.622-3.622a1.5 1.5 0 0 0-1.06-.44Z" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-gray-100" x-text="survey.title"></p>
                            <p x-show="survey.description" x-text="survey.description" class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400"></p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="text-lg font-bold tabular-nums text-gray-900 dark:text-gray-100" x-text="responseCountFor(survey.id)"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.responses_collected') }}</p>
                        </div>

                        <svg class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 transition group-hover:opacity-100 group-hover:translate-x-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                        </svg>
                    </a>
                </template>
            </div>
        </div>

        {{-- My Responses tab: a per-survey breakdown (the "analysis") plus
             the actual list of collected responses, each openable in a
             detail panel. --}}
        <div x-show="activeTab === 'responses'" x-cloak>
            <template x-if="stats?.by_survey?.length > 0">
                <div class="mt-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-5">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('surveys.field.breakdown_title') }}</h2>
                    <div class="space-y-3">
                        <template x-for="row in stats.by_survey" :key="row.survey_id">
                            <div class="flex items-center gap-3">
                                <span class="w-32 shrink-0 truncate text-sm text-gray-600 dark:text-gray-300" x-text="row.survey_title" :title="row.survey_title"></span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-subtle dark:bg-gray-800">
                                    <div
                                        class="h-full rounded-full bg-brand-500 dark:bg-brand-400"
                                        :style="`width: ${Math.max(4, (row.count / Math.max(...stats.by_survey.map(r => r.count))) * 100)}%`"
                                    ></div>
                                </div>
                                <span class="w-8 shrink-0 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100" x-text="row.count"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="responsesLoading" class="mt-6 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('messages.system.loading') }}
            </div>

            <template x-if="!responsesLoading && responsesLoaded && responses.length === 0">
                <div class="mt-6 rounded-card border border-dashed border-border-subtle dark:border-gray-700 py-16 text-center">
                    <h2 class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.field.no_responses_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.field.no_responses_body') }}</p>
                </div>
            </template>

            <div x-show="!responsesLoading && responses.length > 0" class="mt-6 space-y-2">
                <template x-for="response in responses" :key="response.id">
                    <button
                        type="button"
                        @click="openResponse(response)"
                        class="flex w-full flex-col gap-2 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4 text-left transition hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-gray-100" x-text="response.survey_title"></p>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400" x-text="new Date(response.submitted_at || response.created_at).toLocaleString()"></p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="response.status === 'complete'
                                    ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
                                x-text="response.status === 'complete' ? @js(__('surveys.response_status.complete')) : @js(__('surveys.response_status.partial'))"
                            ></span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400 dark:text-gray-500">
                            <span x-show="durationFor(response)" class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <span x-text="durationFor(response)"></span>
                            </span>
                            <span x-show="hasLocation(response)" class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <span x-text="locationLabelFor(response)"></span>
                            </span>
                        </div>
                    </button>
                </template>
            </div>

            <div x-show="!responsesLoading && responsesLastPage > 1" class="mt-4 flex items-center justify-center gap-3">
                <button
                    type="button"
                    @click="goToResponsesPage(responsesPage - 1)"
                    :disabled="responsesPage <= 1"
                    class="rounded-control border border-border-subtle dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-40 disabled:pointer-events-none transition"
                >
                    {{ __('messages.actions.previous') }}
                </button>
                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`${responsesPage} / ${responsesLastPage}`"></span>
                <button
                    type="button"
                    @click="goToResponsesPage(responsesPage + 1)"
                    :disabled="responsesPage >= responsesLastPage"
                    class="rounded-control border border-border-subtle dark:border-gray-700 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-40 disabled:pointer-events-none transition"
                >
                    {{ __('messages.actions.next') }}
                </button>
            </div>
        </div>

        {{-- Response detail panel --}}
        <div
            x-show="selectedResponse"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-8"
            @keydown.escape.window="closeResponse()"
        >
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeResponse()"></div>

            <div class="relative w-full max-w-lg max-h-full overflow-y-auto rounded-2xl border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-2xl p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400" x-text="selectedResponse?.survey_title"></p>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400" x-text="selectedResponse ? new Date(selectedResponse.submitted_at || selectedResponse.created_at).toLocaleString() : ''"></p>
                    </div>
                    <button type="button" @click="closeResponse()" class="shrink-0 rounded-control p-1.5 text-gray-400 dark:text-gray-500 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div x-show="responseDetailLoading" class="mt-6 text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>

                <template x-if="!responseDetailLoading && selectedResponse">
                    <div>
                        <dl class="mt-5 grid grid-cols-2 gap-4 rounded-control bg-surface-subtle dark:bg-gray-800 p-4 text-sm">
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.started_at') }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100" x-text="selectedResponse.started_at ? new Date(selectedResponse.started_at).toLocaleString() : '—'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.submitted_at') }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100" x-text="selectedResponse.submitted_at ? new Date(selectedResponse.submitted_at).toLocaleString() : '—'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.duration') }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100" x-text="durationFor(selectedResponse) ?? '—'"></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.location') }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900 dark:text-gray-100">
                                    <a
                                        x-show="hasLocation(selectedResponse)"
                                        :href="locationMapUrlFor(selectedResponse)"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-brand-600 dark:text-brand-400 hover:underline"
                                        x-text="locationLabelFor(selectedResponse)"
                                    ></a>
                                    <span x-show="!hasLocation(selectedResponse)" class="text-gray-500 dark:text-gray-400">{{ __('surveys.field.location_unavailable') }}</span>
                                </dd>
                            </div>
                        </dl>

                        <dl class="mt-5 space-y-4">
                            <template x-for="answer in (selectedResponse.answers || [])" :key="answer.id">
                                <div class="border-t border-border-subtle dark:border-gray-800 pt-3 first:border-t-0 first:pt-0">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400" x-text="questionLabelFor(selectedResponse.survey_id, answer.survey_question_id)"></dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100" x-text="Array.isArray(answer.value) ? answer.value.join(', ') : answer.value"></dd>
                                </div>
                            </template>
                        </dl>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
