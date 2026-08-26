{{-- Assign/unassign field users for door-to-door collection (spec §7).
     Toggled from builder-header.blade.php via showAssignments. --}}
<div
    x-show="showAssignments"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center px-4"
    @keydown.escape.window="showAssignments = false"
>
    <div class="absolute inset-0 bg-black/40" @click="showAssignments = false"></div>

    <div class="relative w-full max-w-md rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 shadow-xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border-subtle dark:border-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('surveys.builder.field_users_title') }}</h2>
            <button type="button" @click="showAssignments = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.field_users_body') }}</p>

            <div x-show="assignmentsLoading" class="text-sm text-gray-400 dark:text-gray-500">
                {{ __('messages.system.loading') }}
            </div>

            <template x-if="!assignmentsLoading">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                            {{ __('surveys.builder.field_users_title') }}
                        </h3>

                        <p x-show="assignments.length === 0" class="text-sm text-gray-400 dark:text-gray-500">
                            {{ __('surveys.builder.no_field_users_assigned') }}
                        </p>

                        <ul class="space-y-1">
                            <template x-for="assignment in assignments" :key="assignment.id">
                                <li class="flex items-center justify-between rounded-control px-2.5 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate" x-text="assignment.user.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="assignment.user.email"></p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="unassignFieldUser(assignment)"
                                        class="shrink-0 text-xs text-red-600 dark:text-red-400 hover:underline"
                                    >
                                        {{ __('surveys.builder.unassign_field_user') }}
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="border-t border-border-subtle dark:border-gray-800 pt-4">
                        <h3 class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">
                            {{ __('surveys.builder.assign_field_user') }}
                        </h3>

                        <p x-show="assignableUsers.length === 0" class="text-sm text-gray-400 dark:text-gray-500">
                            {{ __('surveys.builder.no_assignable_users') }}
                        </p>

                        <ul class="space-y-1">
                            <template x-for="user in assignableUsers" :key="user.id">
                                <li class="flex items-center justify-between rounded-control px-2.5 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 dark:text-gray-100 truncate" x-text="user.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="user.email"></p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="assignFieldUser(user.id)"
                                        class="shrink-0 text-xs text-brand-600 dark:text-brand-400 hover:underline"
                                    >
                                        {{ __('surveys.builder.assign_field_user') }}
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
