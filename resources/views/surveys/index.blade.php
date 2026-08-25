<x-app-layout :title="__('surveys.builder.all_surveys')">
    <div
        x-data="{
            surveys: [],
            loading: true,
            creating: false,
            newTitle: '',
            error: null,

            async init() {
                const page = await window.surveysApi.surveys.list();
                this.surveys = page.data;
                this.loading = false;
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
        }"
        class="max-w-4xl mx-auto py-8 px-4 sm:px-6"
    >
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.builder.all_surveys') }}</h1>

            <button
                type="button"
                @click="creating = true"
                class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 transition"
            >
                {{ __('surveys.builder.create_survey') }}
            </button>
        </div>

        <div
            x-show="creating"
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

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>

        <div x-show="!loading && surveys.length === 0" x-cloak class="text-center py-16">
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.builder.no_surveys_title') }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.no_surveys_body') }}</p>
        </div>

        <ul x-show="!loading && surveys.length > 0" x-cloak class="divide-y divide-border-subtle dark:divide-gray-800 border border-border-subtle dark:border-gray-800 rounded-card overflow-hidden">
            <template x-for="survey in surveys" :key="survey.id">
                <li>
                    <a :href="`/surveys/${survey.id}`" class="flex items-center justify-between px-4 py-3 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100" x-text="survey.title"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="survey.description || ''"></p>
                        </div>
                        <span
                            class="text-xs font-medium px-2 py-1 rounded-control"
                            :class="survey.is_published
                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                            x-text="survey.is_published ? @js(__('surveys.builder.published_badge')) : @js(__('surveys.builder.draft_badge'))"
                        ></span>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</x-app-layout>
