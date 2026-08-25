@props(['class' => ''])

<div x-data="{ open: false }" @click.outside="open = false" class="relative {{ $class }}">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center justify-center h-9 w-9 rounded-control border border-border-subtle dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
        :aria-expanded="open"
        aria-label="{{ __('messages.nav.settings_appearance') }}"
    >
        {{-- sun (light) --}}
        <svg x-show="$store.theme.resolved === 'light'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        {{-- moon (dark) --}}
        <svg x-show="$store.theme.resolved === 'dark'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 mt-2 w-40 rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 shadow-lg py-1 z-50"
    >
        <template x-for="option in ['light', 'dark', 'system']" :key="option">
            <button
                type="button"
                @click="$store.theme.set(option); open = false"
                class="flex w-full items-center justify-between px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800"
                :class="{ 'font-medium text-brand-600 dark:text-brand-400': $store.theme.preference === option }"
            >
                <span x-text="{
                    light: @js(__('messages.theme.light')),
                    dark: @js(__('messages.theme.dark')),
                    system: @js(__('messages.theme.system')),
                }[option]"></span>
                <svg x-show="$store.theme.preference === option" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </button>
        </template>
    </div>
</div>
