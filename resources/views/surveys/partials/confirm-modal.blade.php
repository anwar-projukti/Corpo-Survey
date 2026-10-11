{{-- Shared delete confirmation modal for the builder (section/question
     delete) — same pattern as resources/views/organizations/index.blade.php
     and resources/views/surveys/index.blade.php, driven by
     askConfirm()/runConfirm()/cancelConfirm() on surveyBuilder(). --}}
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
                :class="confirmVariant === 'danger' ? 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' : 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400'"
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
                :class="confirmVariant === 'danger' ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950' : 'text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950'"
            >
                <span x-show="!confirmBusy">{{ __('messages.actions.confirm') }}</span>
                <span x-show="confirmBusy" x-cloak>{{ __('messages.system.loading') }}</span>
            </button>
        </div>
    </div>
</div>
