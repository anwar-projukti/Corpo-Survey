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
        class="max-w-3xl mx-auto py-8 px-4 sm:px-6"
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">{{ __('surveys.field.my_surveys') }}</h1>

        <div x-show="!online" x-cloak class="mb-4 rounded-card bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-sm px-4 py-3">
            {{ __('surveys.field.offline_banner') }}
        </div>

        <div x-show="queueCount > 0" x-cloak class="mb-4 flex items-center justify-between rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-4 py-3">
            <span
                class="text-sm text-gray-700 dark:text-gray-300"
                x-text="syncing ? @js(__('surveys.field.syncing')) : queuedCountLabel(queueCount)"
            ></span>
            <button
                type="button"
                @click="syncNow()"
                :disabled="syncing || !online"
                class="text-sm text-brand-600 dark:text-brand-400 hover:underline disabled:opacity-50"
            >
                {{ __('surveys.field.sync_now') }}
            </button>
        </div>

        <div x-show="stats" x-cloak class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="stats?.today"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.today_count') }}</p>
            </div>
            <div class="rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="stats?.this_week"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.week_count') }}</p>
            </div>
            <div class="rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="stats?.total"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.field.total_count') }}</p>
            </div>
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>

        <template x-if="!loading && surveys.length === 0">
            <div class="text-center py-16">
                <h2 class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.field.no_assigned_surveys_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.field.no_assigned_surveys_body') }}</p>
            </div>
        </template>

        <div class="space-y-3">
            <template x-for="survey in surveys" :key="survey.id">
                <a
                    :href="`/my/surveys/${survey.id}`"
                    class="block rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 p-4 hover:border-brand-400 transition"
                >
                    <p class="font-medium text-gray-900 dark:text-gray-100" x-text="survey.title"></p>
                    <p x-show="survey.description" x-text="survey.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                </a>
            </template>
        </div>
    </div>
</x-app-layout>
