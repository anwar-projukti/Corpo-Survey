<x-app-layout :title="__('surveys.imports.title')">
    @php
        $importsI18n = [
            'upload_failed' => __('surveys.imports.upload_failed'),
            'map_name_required' => __('surveys.imports.map_name_required'),
            'confirm_failed' => __('surveys.imports.confirm_failed'),
            'load_failed' => __('surveys.imports.load_failed'),
        ];
    @endphp
    <script>
        window.__i18n = @json($importsI18n);
    </script>

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.imports.title') }}</h1>
        </div>

        <div
            x-data="importWizard()"
            x-init="init()"
            class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6"
        >
            {{-- Step: upload --}}
            <template x-if="step === 'upload'">
                <div>
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ __('surveys.imports.upload_title') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('surveys.imports.upload_body') }}</p>

                    <div
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="onDrop($event)"
                        class="rounded-card border-2 border-dashed p-8 text-center transition"
                        :class="dragOver ? 'border-brand-500 bg-brand-50 dark:bg-brand-950' : 'border-border-subtle dark:border-gray-700'"
                    >
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3" x-show="!uploading">{{ __('surveys.imports.drop_file') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-show="uploading" x-cloak>{{ __('surveys.imports.uploading') }}</p>

                        <label x-show="!uploading" class="inline-block cursor-pointer rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition">
                            {{ __('surveys.imports.choose_file') }}
                            <input type="file" class="sr-only" accept=".xlsx,.xls,.csv" @change="onFileChange($event)">
                        </label>
                    </div>

                    <p x-show="uploadError" x-text="uploadError" x-cloak class="mt-3 text-sm text-red-600 dark:text-red-400"></p>
                </div>
            </template>

            {{-- Step: map columns --}}
            <template x-if="step === 'map'">
                <div>
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ __('surveys.imports.map_title') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('surveys.imports.map_body') }}</p>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.imports.map_name') }} <span class="text-red-500">*</span></label>
                            <select x-model="mapping.name" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <option value="">{{ __('surveys.imports.map_not_mapped') }}</option>
                                <template x-for="column in detectedColumns" :key="column">
                                    <option :value="column" x-text="column"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.imports.map_email') }}</label>
                            <select x-model="mapping.email" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <option value="">{{ __('surveys.imports.map_not_mapped') }}</option>
                                <template x-for="column in detectedColumns" :key="column">
                                    <option :value="column" x-text="column"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.imports.map_phone') }}</label>
                            <select x-model="mapping.phone" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <option value="">{{ __('surveys.imports.map_not_mapped') }}</option>
                                <template x-for="column in detectedColumns" :key="column">
                                    <option :value="column" x-text="column"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <p x-show="confirmError" x-text="confirmError" x-cloak class="mt-3 text-sm text-red-600 dark:text-red-400"></p>

                    <button
                        type="button"
                        :disabled="confirming"
                        @click="confirmMapping()"
                        class="mt-4 rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3 py-1.5 transition"
                    >
                        <span x-show="!confirming">{{ __('surveys.imports.confirm') }}</span>
                        <span x-show="confirming" x-cloak>{{ __('surveys.imports.confirming') }}</span>
                    </button>
                </div>
            </template>

            {{-- Step: processing --}}
            <template x-if="step === 'processing'">
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.imports.processing') }}</p>
                </div>
            </template>

            {{-- Step: summary --}}
            <template x-if="step === 'summary'">
                <div>
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('surveys.imports.summary_title') }}</h2>

                    <template x-if="importJob?.status === 'completed'">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                            <div class="rounded-control bg-surface-subtle dark:bg-gray-800 p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.imports.summary_total') }}</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="importJob.total_rows"></p>
                            </div>
                            <div class="rounded-control bg-green-50 dark:bg-green-950 p-3">
                                <p class="text-xs text-green-700 dark:text-green-300">{{ __('surveys.imports.summary_imported') }}</p>
                                <p class="text-lg font-semibold text-green-700 dark:text-green-300" x-text="importJob.imported_count"></p>
                            </div>
                            <div class="rounded-control bg-surface-subtle dark:bg-gray-800 p-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('surveys.imports.summary_duplicates') }}</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="importJob.duplicate_count"></p>
                            </div>
                            <div class="rounded-control bg-red-50 dark:bg-red-950 p-3">
                                <p class="text-xs text-red-700 dark:text-red-300">{{ __('surveys.imports.summary_failed') }}</p>
                                <p class="text-lg font-semibold text-red-700 dark:text-red-300" x-text="importJob.failed_count"></p>
                            </div>
                        </div>
                    </template>

                    <template x-if="importJob?.status === 'failed'">
                        <p class="text-sm text-red-600 dark:text-red-400 mb-4" x-text="importJob.failure_reason"></p>
                    </template>

                    <template x-if="importJob?.row_errors?.length">
                        <div class="mb-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.imports.row_errors_title') }}</p>
                            <ul class="text-xs text-gray-600 dark:text-gray-300 space-y-0.5 max-h-40 overflow-y-auto">
                                <template x-for="(rowError, index) in importJob.row_errors" :key="index">
                                    <li>{{ __('surveys.imports.row') }} <span x-text="rowError.row"></span>: <span x-text="rowError.error"></span></li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <button type="button" @click="startNew()" class="rounded-control border border-border-subtle dark:border-gray-700 text-sm px-3 py-1.5 text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800">
                        {{ __('surveys.imports.start_new') }}
                    </button>
                </div>
            </template>
        </div>

        {{-- History --}}
        <div class="mt-8">
            <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">{{ __('surveys.imports.history_title') }}</h2>

            <div x-data="importHistory()" x-init="init()" @import-completed.window="refresh()">
                <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
                <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

                <template x-if="!loading && !loadError && imports.length === 0">
                    <div class="text-center py-12">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.imports.none_title') }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.imports.none_body') }}</p>
                    </div>
                </template>

                <template x-if="!loading && imports.length > 0">
                    <div class="overflow-x-auto border border-border-subtle dark:border-gray-800 rounded-card">
                        <table class="min-w-full text-sm">
                            <thead class="bg-surface-subtle dark:bg-gray-900 text-left">
                                <tr>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_filename') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_status') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_imported') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_duplicates') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_failed') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_requested_by') }}</th>
                                    <th class="px-3 py-2">{{ __('surveys.imports.column_requested_at') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                                <template x-for="job in imports" :key="job.id">
                                    <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800">
                                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="job.original_filename"></td>
                                        <td class="px-3 py-2">
                                            <span
                                                class="text-xs px-2 py-0.5 rounded-control"
                                                :class="{
                                                    uploaded: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                                    mapping_confirmed: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                                    processing: 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300',
                                                    completed: 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
                                                    failed: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                                                }[job.status]"
                                                x-text="{
                                                    uploaded: @js(__('surveys.imports.status_uploaded')),
                                                    mapping_confirmed: @js(__('surveys.imports.status_mapping_confirmed')),
                                                    processing: @js(__('surveys.imports.status_processing')),
                                                    completed: @js(__('surveys.imports.status_completed')),
                                                    failed: @js(__('surveys.imports.status_failed')),
                                                }[job.status]"
                                            ></span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.imported_count ?? '—'"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.duplicate_count ?? '—'"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.failed_count ?? '—'"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.requested_by ?? '—'"></td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="job.created_at ? new Date(job.created_at).toLocaleString() : '—'"></td>
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
        </div>
    </div>
</x-app-layout>
