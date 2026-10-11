<x-app-layout :title="__('surveys.organizations.title')">
    @php
        $organizationsI18n = [
            'load_failed' => __('surveys.organizations.load_failed'),
            'save_failed' => __('surveys.organizations.save_failed'),
            'delete_failed' => __('surveys.organizations.delete_failed'),
            'delete_title' => __('surveys.organizations.delete_title'),
            'delete_confirm' => __('surveys.organizations.delete_confirm'),
            'restore_failed' => __('surveys.organizations.restore_failed'),
            'force_delete_failed' => __('surveys.organizations.force_delete_failed'),
            'force_delete_title' => __('surveys.organizations.force_delete_title'),
            'force_delete_confirm' => __('surveys.organizations.force_delete_confirm'),
        ];
    @endphp
    <script>
        window.__i18n = @json($organizationsI18n);
    </script>

    <div x-data="organizationManagement()" x-init="init()" class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100">{{ __('surveys.organizations.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <span x-show="!showTrashed">{{ __('surveys.organizations.body') }}</span>
                    <span x-show="showTrashed" x-cloak>{{ __('surveys.organizations.deleted_body') }}</span>
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="toggleTrashedView()" class="h-9 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium px-3.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                    <span x-show="!showTrashed">{{ __('surveys.organizations.view_deleted') }}</span>
                    <span x-show="showTrashed" x-cloak>{{ __('surveys.organizations.view_active') }}</span>
                </button>
                <button type="button" @click="openForm()" class="inline-flex h-9 items-center gap-1.5 rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-3.5 shadow-sm shadow-brand-600/20 hover:shadow-md transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('surveys.organizations.add') }}
                </button>
            </div>
        </div>

        {{-- Summary strip — only meaningful for the active list; the trashed
             view has its own body copy above instead. --}}
        <div x-show="!loading && !loadError && !showTrashed" x-cloak class="mt-6 grid grid-cols-3 gap-4">
            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100" x-text="organizations.length"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.organizations.stat_total') }}</p>
            </div>
            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-extrabold tracking-tight text-green-600 dark:text-green-400" x-text="activeCount"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.organizations.status_active') }}</p>
            </div>
            <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
                <p class="text-2xl font-extrabold tracking-tight text-gray-400 dark:text-gray-500" x-text="suspendedCount"></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.organizations.status_suspended') }}</p>
            </div>
        </div>

        {{-- Add organization form --}}
        <div x-show="showForm" x-cloak class="mt-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-5">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('surveys.organizations.add') }}</h2>
            <div class="grid sm:grid-cols-2 gap-3 mb-1">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.organizations.name') }}</label>
                    <input
                        type="text"
                        x-model="formName"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2.5 py-1.5"
                    >
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.organizations.slug') }}</label>
                    <input
                        type="text"
                        x-model="formSlug"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2.5 py-1.5"
                    >
                </div>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">{{ __('surveys.organizations.slug_help') }}</p>

            <p x-show="submitError" x-text="submitError" x-cloak class="mb-3 text-sm text-red-600 dark:text-red-400"></p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="submitting"
                    @click="submit()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3.5 py-1.5 transition"
                >
                    <span x-show="!submitting">{{ __('surveys.organizations.save') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('surveys.organizations.saving') }}</span>
                </button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                    {{ __('surveys.organizations.cancel') }}
                </button>
            </div>
        </div>

        <div x-show="!showTrashed" class="mt-6">
            <div x-show="loading" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-8">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('messages.system.loading') }}
            </div>
            <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

            <template x-if="!loading && !loadError && organizations.length === 0">
                <div class="rounded-card border border-dashed border-border-subtle dark:border-gray-700 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <p class="mt-4 font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.organizations.none_title') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.organizations.none_body') }}</p>
                </div>
            </template>

            <template x-if="!loading && organizations.length > 0">
                <div class="overflow-x-auto rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-border-subtle dark:border-gray-800 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_name') }}</th>
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_admin_email') }}</th>
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_users') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_surveys') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_responses') }}</th>
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_created') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                            <template x-for="organization in organizations" :key="organization.id">
                                <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800/60 transition">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300" x-text="organization.name.charAt(0).toUpperCase()"></div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="organization.name"></p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="organization.slug"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        <template x-if="organization.admin_emails.length === 0">
                                            <span class="text-gray-400 dark:text-gray-500">{{ __('surveys.organizations.no_admin') }}</span>
                                        </template>
                                        <template x-for="email in organization.admin_emails" :key="email">
                                            <div x-text="email"></div>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-1 rounded-full"
                                            :class="organization.status === 'active'
                                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="organization.status === 'active' ? 'bg-green-500' : 'bg-gray-400 dark:bg-gray-500'"></span>
                                            <span
                                                x-text="organization.status === 'active'
                                                    ? @js(__('surveys.organizations.status_active'))
                                                    : @js(__('surveys.organizations.status_suspended'))"
                                            ></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.user_count"></td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.survey_count"></td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.response_count"></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="new Date(organization.created_at).toLocaleDateString()"></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                :disabled="togglingId === organization.id"
                                                @click="toggleStatus(organization)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition"
                                                :title="organization.status === 'active'
                                                    ? @js(__('surveys.organizations.suspend'))
                                                    : @js(__('surveys.organizations.activate'))"
                                                :aria-label="organization.status === 'active'
                                                    ? @js(__('surveys.organizations.suspend'))
                                                    : @js(__('surveys.organizations.activate'))"
                                            >
                                                {{-- pause (suspend) --}}
                                                <svg x-show="organization.status === 'active'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                                                </svg>
                                                {{-- play (activate) --}}
                                                <svg x-show="organization.status !== 'active'" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                                </svg>
                                            </button>

                                            <template x-if="organization.status === 'active'">
                                                <form :action="`/organizations/${organization.id}/impersonate`" method="POST" class="inline-flex">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                                                        title="{{ __('surveys.organizations.impersonate') }}"
                                                        aria-label="{{ __('surveys.organizations.impersonate') }}"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M3 12h13.5m0 0-3-3m3 3-3 3" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </template>

                                            <button
                                                type="button"
                                                :disabled="deletingId === organization.id"
                                                @click="confirmDeleteOrganization(organization)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition"
                                                title="{{ __('surveys.organizations.delete') }}"
                                                aria-label="{{ __('surveys.organizations.delete') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        {{-- Deleted (soft-deleted) organizations --}}
        <div x-show="showTrashed" x-cloak class="mt-6">
            <div x-show="trashedLoading" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-8">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('messages.system.loading') }}
            </div>
            <p x-show="trashedLoadError" x-text="trashedLoadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

            <template x-if="!trashedLoading && !trashedLoadError && trashedOrganizations.length === 0">
                <div class="rounded-card border border-dashed border-border-subtle dark:border-gray-700 py-16 text-center">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.organizations.none_deleted_title') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.organizations.none_deleted_body') }}</p>
                </div>
            </template>

            <template x-if="!trashedLoading && trashedOrganizations.length > 0">
                <div class="overflow-x-auto rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-border-subtle dark:border-gray-800 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_name') }}</th>
                                <th class="px-4 py-3">{{ __('surveys.organizations.column_admin_email') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_users') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_surveys') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('surveys.organizations.column_responses') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                            <template x-for="organization in trashedOrganizations" :key="organization.id">
                                <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800/60 transition">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-gray-100 text-sm font-semibold text-gray-400 dark:bg-gray-800 dark:text-gray-500" x-text="organization.name.charAt(0).toUpperCase()"></div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 dark:text-gray-100 truncate" x-text="organization.name"></p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="organization.slug"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        <template x-if="organization.admin_emails.length === 0">
                                            <span class="text-gray-400 dark:text-gray-500">{{ __('surveys.organizations.no_admin') }}</span>
                                        </template>
                                        <template x-for="email in organization.admin_emails" :key="email">
                                            <div x-text="email"></div>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.user_count"></td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.survey_count"></td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-gray-300" x-text="organization.response_count"></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                :disabled="restoringId === organization.id"
                                                @click="restoreOrganization(organization)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition"
                                                title="{{ __('surveys.organizations.restore') }}"
                                                aria-label="{{ __('surveys.organizations.restore') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                :disabled="forceDeletingId === organization.id"
                                                @click="confirmForceDeleteOrganization(organization)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition"
                                                title="{{ __('surveys.organizations.delete_permanently') }}"
                                                aria-label="{{ __('surveys.organizations.delete_permanently') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        {{-- Shared delete confirmation modal — driven by askConfirm()/confirmAction
             so both the soft-delete and permanent-delete actions reuse the
             same dialog instead of the browser's unstyled confirm(). --}}
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
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                        :class="confirmDanger
                            ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400'
                            : 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400'"
                    >
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
                        class="flex-1 px-4 py-3 text-sm font-semibold transition disabled:opacity-60"
                        :class="confirmDanger
                            ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950'
                            : 'text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950'"
                    >
                        <span x-show="!confirmBusy">{{ __('messages.actions.delete') }}</span>
                        <span x-show="confirmBusy" x-cloak>{{ __('messages.system.loading') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
