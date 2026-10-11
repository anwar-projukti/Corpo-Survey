<x-app-layout :title="__('surveys.team.title')">
    @php
        $teamI18n = [
            'load_failed' => __('surveys.team.load_failed'),
            'save_failed' => __('surveys.team.save_failed'),
            'delete_failed' => __('surveys.team.delete_failed'),
            'delete_title' => __('surveys.team.delete_title'),
            'delete_confirm' => __('surveys.team.delete_confirm'),
        ];
    @endphp
    <script>
        window.__i18n = @json($teamI18n);
    </script>

    <div x-data="userManagement()" x-init="init()" class="max-w-4xl mx-auto py-8 px-4 sm:px-6">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.team.title') }}</h1>
            <button type="button" @click="openForm()" class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition">
                {{ __('surveys.team.add') }}
            </button>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('surveys.team.body') }}</p>

        {{-- Add/edit teammate form --}}
        <div x-show="showForm" x-cloak class="mb-6 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                <span x-show="!isEditing">{{ __('surveys.team.add') }}</span>
                <span x-show="isEditing" x-cloak>{{ __('surveys.team.edit') }}</span>
            </h2>

            <div class="grid sm:grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.team.name') }}</label>
                    <input
                        type="text"
                        x-model="formName"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                    >
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.team.email') }}</label>
                    <input
                        type="email"
                        x-model="formEmail"
                        :disabled="isEditing"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                        <span x-show="!isEditing">{{ __('surveys.team.password') }}</span>
                        <span x-show="isEditing" x-cloak>{{ __('surveys.team.new_password') }}</span>
                    </label>
                    <input
                        type="password"
                        x-model="formPassword"
                        :placeholder="isEditing ? @js(__('surveys.team.leave_blank_to_keep')) : ''"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                    >
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.team.password_confirmation') }}</label>
                    <input
                        type="password"
                        x-model="formPasswordConfirmation"
                        class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                    >
                </div>
            </div>

            <p x-show="!isEditing" class="text-xs text-gray-400 dark:text-gray-500 mb-3">{{ __('surveys.team.password_body') }}</p>

            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('surveys.team.role') }}</label>
            <select x-model="formRole" class="w-full mb-3 rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                <template x-for="role in assignableRoles" :key="role">
                    <option
                        :value="role"
                        x-text="{
                            'Editor': @js(__('surveys.team.role_editor')),
                            'Field User': @js(__('surveys.team.role_field_user')),
                            'Viewer': @js(__('surveys.team.role_viewer')),
                        }[role]"
                    ></option>
                </template>
            </select>

            <p x-show="submitError" x-text="submitError" x-cloak class="mb-2 text-sm text-red-600 dark:text-red-400"></p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="submitting"
                    @click="submit()"
                    class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-3 py-1.5 transition"
                >
                    <span x-show="!submitting">{{ __('surveys.team.save') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('surveys.team.saving') }}</span>
                </button>
                <button type="button" @click="showForm = false" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                    {{ __('surveys.team.cancel') }}
                </button>
            </div>
        </div>

        <div x-show="!showForm">
        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && teammates.length === 0">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.team.none_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.team.none_body') }}</p>
            </div>
        </template>

        <template x-if="!loading && teammates.length > 0">
            <div class="overflow-x-auto border border-border-subtle dark:border-gray-800 rounded-card">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-subtle dark:bg-gray-900 text-left">
                        <tr>
                            <th class="px-3 py-2">{{ __('surveys.team.column_no') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.team.column_name') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.team.column_email') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.team.column_role') }}</th>
                            <th class="px-3 py-2">{{ __('surveys.team.column_added') }}</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-gray-800">
                        <template x-for="(teammate, index) in teammates" :key="teammate.id">
                            <tr class="hover:bg-surface-subtle dark:hover:bg-gray-800">
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400" x-text="index + 1"></td>
                                <td class="px-3 py-2 text-gray-900 dark:text-gray-100" x-text="teammate.name"></td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" x-text="teammate.email"></td>
                                <td class="px-3 py-2">
                                    <span class="text-xs px-2 py-0.5 rounded-control bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300" x-text="teammate.role"></span>
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400" x-text="new Date(teammate.created_at).toLocaleDateString()"></td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <template x-if="teammate.role !== 'Org Admin'">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="openEditForm(teammate)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                                                title="{{ __('surveys.team.edit') }}"
                                                aria-label="{{ __('surveys.team.edit') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="deletingId === teammate.id"
                                                @click="confirmDeleteTeammate(teammate)"
                                                class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition"
                                                title="{{ __('surveys.team.delete') }}"
                                                aria-label="{{ __('surveys.team.delete') }}"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
        </div>

        {{-- Delete confirmation modal --}}
        <div
            x-show="confirmOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center px-4"
            @keydown.escape.window="!confirmBusy && cancelConfirm()"
        >
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="!confirmBusy && cancelConfirm()"></div>

            <div
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
                        class="flex-1 px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition disabled:opacity-60"
                    >
                        <span x-show="!confirmBusy">{{ __('messages.actions.delete') }}</span>
                        <span x-show="confirmBusy" x-cloak>{{ __('messages.system.loading') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
