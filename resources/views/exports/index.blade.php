<x-app-layout :title="__('surveys.exports.title')">
    @php
        $exportsI18n = [
            'load_failed' => __('surveys.exports.load_failed'),
            'start_failed' => __('surveys.exports.start_failed'),
        ];
    @endphp
    <script>
        window.__i18n = @json($exportsI18n);
    </script>

    <div x-data="exportHistory({{ $organization->id }})" x-init="init()" class="max-w-5xl mx-auto py-8 px-4 sm:px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.exports.title') }}</h1>
            </div>
            <button type="button" @click="load()" class="rounded-control border border-border-subtle dark:border-gray-700 text-sm px-3 py-1.5 text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800">
                {{ __('surveys.exports.refresh') }}
            </button>
        </div>

        {{-- Org-wide export trigger --}}
        <div class="mb-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.exports.export_organization') }}</h2>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.exports.format') }}</label>
                    <select x-model="format" class="rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

                <button
                    type="button"
                    :disabled="starting"
                    @click="startOrganizationExport()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3 py-1.5 transition"
                >
                    <span x-show="!starting">{{ __('surveys.exports.submit') }}</span>
                    <span x-show="starting" x-cloak>{{ __('surveys.exports.submitting') }}</span>
                </button>
            </div>

            <p x-show="startError" x-text="startError" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
        </div>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && exports.length === 0">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.exports.none_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.exports.none_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && exports.length > 0">
            <div class="overflow-x-auto border border-border-subtle dark:border-gray-800 rounded-card">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-subtle dark:bg-gray-900 text-left">
                        <tr>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_type') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_format') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_status') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_rows') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_requested_by') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.exports.column_requested_at') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                        <template x-for="job in exports" :key="job.id">
                            <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800">
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.type === 'organization' ? @js(__('surveys.exports.type_organization')) : @js(__('surveys.exports.type_survey'))"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300 uppercase" x-text="job.format"></td>
                                <td class="px-3 py-2">
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-control"
                                        :class="{
                                            pending: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                            completed: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
                                            failed: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                                        }[job.status]"
                                        x-text="{
                                            pending: @js(__('surveys.exports.status_pending')),
                                            completed: @js(__('surveys.exports.status_completed')),
                                            failed: @js(__('surveys.exports.status_failed')),
                                        }[job.status]"
                                    ></span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.row_count ?? '—'"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.requested_by ?? '—'"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.created_at ? new Date(job.created_at).toLocaleString() : '—'"></td>
                                <td class="px-3 py-2">
                                    <a x-show="job.download_url" :href="job.download_url" class="text-brand-600 dark:text-brand-400 hover:underline">
                                        {{ __('surveys.exports.download') }}
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
