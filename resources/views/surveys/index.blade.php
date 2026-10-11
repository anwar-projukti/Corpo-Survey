<x-app-layout :title="__('surveys.builder.all_surveys')">
    <div
        x-data="{
            surveys: [],
            loading: true,
            creating: false,
            newTitle: '',
            error: null,

            showTrashed: false,
            trashedSurveys: [],
            trashedLoaded: false,
            trashedLoading: false,
            trashedError: null,

            closingId: null,
            deletingId: null,
            restoringId: null,

            confirmOpen: false,
            confirmTitle: '',
            confirmBody: '',
            confirmBusy: false,
            confirmAction: null,

            async init() {
                const page = await window.surveysApi.surveys.list();
                this.surveys = page.data;
                this.loading = false;
            },

            async toggleTrashedView() {
                this.showTrashed = !this.showTrashed;
                if (this.showTrashed && !this.trashedLoaded) {
                    await this.loadTrashed();
                }
            },

            async loadTrashed() {
                this.trashedLoading = true;
                this.trashedError = null;
                try {
                    const page = await window.surveysApi.surveys.list({ trashed: 1 });
                    this.trashedSurveys = page.data;
                    this.trashedLoaded = true;
                } catch {
                    this.trashedError = @js(__('surveys.builder.load_failed'));
                } finally {
                    this.trashedLoading = false;
                }
            },

            async restoreSurvey(survey) {
                this.restoringId = survey.id;
                try {
                    await window.surveysApi.surveys.restore(survey.id);
                    this.trashedSurveys = this.trashedSurveys.filter((s) => s.id !== survey.id);
                    this.loading = true;
                    const page = await window.surveysApi.surveys.list();
                    this.surveys = page.data;
                    this.loading = false;
                } catch {
                    this.trashedError = @js(__('surveys.builder.restore_survey_failed'));
                } finally {
                    this.restoringId = null;
                }
            },

            async createSurvey() {
                this.error = null;
                try {
                    const survey = await window.surveysApi.surveys.create({ title: this.newTitle });
                    window.location = `/surveys/${survey.id}`;
                } catch (e) {
                    this.error = e?.response?.data?.errors?.title?.[0] || @js(__('messages.system.generic_error'));
                }
            },

            askConfirm({ title, body, onConfirm }) {
                this.confirmTitle = title;
                this.confirmBody = body;
                this.confirmAction = onConfirm;
                this.confirmOpen = true;
            },

            async runConfirm() {
                if (!this.confirmAction) return;
                this.confirmBusy = true;
                try {
                    await this.confirmAction();
                    this.confirmOpen = false;
                } finally {
                    this.confirmBusy = false;
                    this.confirmAction = null;
                }
            },

            cancelConfirm() {
                this.confirmOpen = false;
                this.confirmAction = null;
            },

            confirmCloseSurvey(survey) {
                this.askConfirm({
                    title: @js(__('surveys.builder.close_survey_title')),
                    body: @js(__('surveys.builder.close_survey_confirm')).replace(':title', survey.title),
                    onConfirm: () => this.closeSurvey(survey),
                });
            },

            async closeSurvey(survey) {
                this.closingId = survey.id;
                try {
                    const updated = await window.surveysApi.surveys.close(survey.id);
                    survey.is_closed = updated.is_closed;
                } catch {
                    this.error = @js(__('surveys.builder.close_survey_failed'));
                } finally {
                    this.closingId = null;
                }
            },

            confirmDeleteSurvey(survey) {
                this.askConfirm({
                    title: @js(__('surveys.builder.delete_survey_title')),
                    body: @js(__('surveys.builder.delete_survey_confirm')).replace(':title', survey.title),
                    onConfirm: () => this.deleteSurvey(survey),
                });
            },

            async deleteSurvey(survey) {
                this.deletingId = survey.id;
                try {
                    await window.surveysApi.surveys.destroy(survey.id);
                    this.surveys = this.surveys.filter((s) => s.id !== survey.id);
                } catch {
                    this.error = @js(__('surveys.builder.delete_survey_failed'));
                } finally {
                    this.deletingId = null;
                }
            },
        }"
        class="max-w-4xl mx-auto py-8 px-4 sm:px-6"
    >
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="showTrashed ? @js(__('surveys.builder.view_deleted_surveys')) : @js(__('surveys.builder.all_surveys'))"></h1>

            <div class="flex items-center gap-2">
                @can('survey.delete')
                    <button
                        type="button"
                        @click="toggleTrashedView()"
                        class="rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                    >
                        <span x-show="!showTrashed">{{ __('surveys.builder.view_deleted_surveys') }}</span>
                        <span x-show="showTrashed" x-cloak>{{ __('surveys.builder.view_active_surveys') }}</span>
                    </button>
                @endcan

                @can('create', \App\Models\Survey::class)
                    <button
                        type="button"
                        x-show="!showTrashed"
                        @click="creating = true"
                        class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 transition"
                    >
                        {{ __('surveys.builder.create_survey') }}
                    </button>
                @endcan
            </div>
        </div>

        <p x-show="showTrashed" x-cloak class="text-sm text-gray-500 dark:text-gray-400 mb-6">
            {{ __('surveys.builder.deleted_surveys_body') }}
        </p>
        <div x-show="!showTrashed" class="mb-6"></div>

        @can('create', \App\Models\Survey::class)
            <div
                x-show="creating && !showTrashed"
                x-cloak
                x-transition
                @keydown.escape.window="creating = false"
                class="mb-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6"
            >
                <h2 class="font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.builder.new_survey') }}</h2>

                <label for="new-survey-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('surveys.builder.survey_title') }}
                </label>
                <input
                    id="new-survey-title"
                    type="text"
                    x-model="newTitle"
                    @keydown.enter="createSurvey"
                    placeholder="{{ __('surveys.builder.survey_title_placeholder') }}"
                    class="mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
                >
                <p x-show="error" x-text="error" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>

                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        @click="createSurvey"
                        :disabled="!newTitle"
                        class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 transition"
                    >
                        {{ __('messages.actions.create') }}
                    </button>
                    <button
                        type="button"
                        @click="creating = false; newTitle = ''; error = null"
                        class="rounded-control text-sm text-gray-600 dark:text-gray-400 px-4 py-2 hover:bg-surface-subtle dark:hover:bg-gray-800"
                    >
                        {{ __('messages.actions.cancel') }}
                    </button>
                </div>
            </div>
        @endcan

        <div x-show="!showTrashed">
            <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>

            <p x-show="error && !loading" x-text="error" x-cloak class="mb-4 text-sm text-red-600 dark:text-red-400"></p>

            <div x-show="!loading && surveys.length === 0" x-cloak class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.builder.no_surveys_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.no_surveys_body') }}</p>
            </div>

            <ul x-show="!loading && surveys.length > 0" x-cloak class="divide-y divide-border-subtle dark:divide-gray-800 border border-border-subtle dark:border-gray-800 rounded-card overflow-hidden">
                <template x-for="(survey, index) in surveys" :key="survey.id">
                    <li class="flex items-center gap-3 px-4 py-3 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                        <span class="w-6 shrink-0 text-sm text-gray-400 dark:text-gray-500 text-right" x-text="index + 1"></span>

                        <a :href="`/surveys/${survey.id}`" class="flex-1 min-w-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="survey.title"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="survey.description || ''"></p>
                            </div>
                            <span
                                class="shrink-0 text-xs font-medium px-2 py-1 rounded-control"
                                :class="survey.is_closed
                                    ? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                                    : (survey.is_published
                                        ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300')"
                                x-text="survey.is_closed
                                    ? @js(__('surveys.builder.closed_badge'))
                                    : (survey.is_published ? @js(__('surveys.builder.published_badge')) : @js(__('surveys.builder.draft_badge')))"
                            ></span>
                        </a>

                        @canany(['survey.publish', 'survey.delete'])
                            <div class="shrink-0 inline-flex items-center gap-1">
                                @can('survey.publish')
                                    <button
                                        type="button"
                                        x-show="!survey.is_closed"
                                        :disabled="closingId === survey.id"
                                        @click.prevent.stop="confirmCloseSurvey(survey)"
                                        class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-surface dark:hover:bg-gray-900 disabled:opacity-50 transition"
                                        title="{{ __('surveys.builder.close_survey') }}"
                                        aria-label="{{ __('surveys.builder.close_survey') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                @endcan

                                @can('survey.delete')
                                    <button
                                        type="button"
                                        :disabled="deletingId === survey.id"
                                        @click.prevent.stop="confirmDeleteSurvey(survey)"
                                        class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-surface dark:hover:bg-gray-900 disabled:opacity-50 transition"
                                        title="{{ __('surveys.builder.delete_survey') }}"
                                        aria-label="{{ __('surveys.builder.delete_survey') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                @endcan
                            </div>
                        @endcanany
                    </li>
                </template>
            </ul>
        </div>

        {{-- Deleted (soft-deleted) surveys --}}
        <div x-show="showTrashed" x-cloak>
            <div x-show="trashedLoading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>

            <p x-show="trashedError && !trashedLoading" x-text="trashedError" x-cloak class="mb-4 text-sm text-red-600 dark:text-red-400"></p>

            <div x-show="!trashedLoading && trashedSurveys.length === 0" x-cloak class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.builder.none_deleted_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.none_deleted_body') }}</p>
            </div>

            <ul x-show="!trashedLoading && trashedSurveys.length > 0" x-cloak class="divide-y divide-border-subtle dark:divide-gray-800 border border-border-subtle dark:border-gray-800 rounded-card overflow-hidden">
                <template x-for="(survey, index) in trashedSurveys" :key="survey.id">
                    <li class="flex items-center gap-3 px-4 py-3 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                        <span class="w-6 shrink-0 text-sm text-gray-400 dark:text-gray-500 text-right" x-text="index + 1"></span>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="survey.title"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="survey.description || ''"></p>
                        </div>

                        @can('survey.delete')
                            <button
                                type="button"
                                :disabled="restoringId === survey.id"
                                @click="restoreSurvey(survey)"
                                class="shrink-0 p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface dark:hover:bg-gray-900 disabled:opacity-50 transition"
                                title="{{ __('surveys.builder.restore_survey') }}"
                                aria-label="{{ __('surveys.builder.restore_survey') }}"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                </svg>
                            </button>
                        @endcan
                    </li>
                </template>
            </ul>
        </div>

        {{-- Shared confirmation modal for deactivate/delete — see
             resources/views/organizations/index.blade.php for the same pattern. --}}
        <div
            x-show="confirmOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4"
            @keydown.escape.window="!confirmBusy && cancelConfirm()"
        >
            <div
                x-show="confirmOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                @click="!confirmBusy && cancelConfirm()"
            ></div>

            <div
                x-show="confirmOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                role="alertdialog"
                aria-modal="true"
                :aria-label="confirmTitle"
                class="relative w-full max-w-sm rounded-2xl border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-2xl ring-1 ring-black/5 overflow-hidden"
            >
                <div class="px-6 pt-6 pb-5">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <h2 class="mt-4 text-center text-base font-semibold text-gray-900 dark:text-gray-100" x-text="confirmTitle"></h2>
                    <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400" x-text="confirmBody"></p>
                </div>

                <div class="flex border-t border-border-subtle dark:border-gray-800">
                    <button
                        type="button"
                        :disabled="confirmBusy"
                        @click="cancelConfirm()"
                        class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition border-r border-border-subtle dark:border-gray-800"
                    >
                        {{ __('messages.actions.cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="confirmBusy"
                        @click="runConfirm()"
                        class="flex-1 px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 disabled:opacity-60 transition"
                    >
                        <span x-show="!confirmBusy">{{ __('messages.actions.confirm') }}</span>
                        <span x-show="confirmBusy" x-cloak>{{ __('messages.system.loading') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
