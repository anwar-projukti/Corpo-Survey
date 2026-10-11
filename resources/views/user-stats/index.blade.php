<x-app-layout :title="__('surveys.user_stats.title')">
    @php
        $userStatsI18n = [
            'load_failed' => __('surveys.user_stats.load_failed'),
        ];
    @endphp
    <script>
        window.__i18n = @json($userStatsI18n);
    </script>

    <div x-data="userStats({{ $organization->id }})" x-init="init()" class="max-w-5xl mx-auto py-8 px-4 sm:px-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.user_stats.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.user_stats.body') }}</p>
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && stats.length === 0">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.user_stats.none_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.user_stats.none_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && stats.length > 0">
            <div class="overflow-x-auto border border-border-subtle dark:border-gray-800 rounded-card">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-subtle dark:bg-gray-900 text-left">
                        <tr>
                            <th class="px-3 py-2">{{ __('surveys.user_stats.column_no') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.user_stats.column_user') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.user_stats.column_survey') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.user_stats.column_response_count') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.user_stats.column_last_submitted') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                        <template x-for="(stat, index) in stats" :key="`${stat.user_id}-${stat.survey_id}`">
                            <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800">
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400" x-text="(meta ? (meta.current_page - 1) * meta.per_page : 0) + index + 1"></td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="stat.user_name"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="stat.survey_title"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300 font-medium" x-text="stat.response_count"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="stat.last_submitted_at ? new Date(stat.last_submitted_at).toLocaleString() : '—'"></td>
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
