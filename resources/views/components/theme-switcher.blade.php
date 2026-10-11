@props(['class' => ''])

<button
    type="button"
    x-data
    role="switch"
    :aria-checked="$store.theme.resolved === 'dark'"
    @click="$store.theme.set($store.theme.resolved === 'dark' ? 'light' : 'dark')"
    aria-label="{{ __('messages.nav.settings_appearance') }}"
    class="relative inline-flex h-9 w-16 items-center rounded-full border border-border-subtle dark:border-gray-700 bg-surface-subtle dark:bg-gray-800 transition {{ $class }}"
>
    <span class="absolute left-2 flex items-center justify-center">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
    </span>
    <span class="absolute right-2 flex items-center justify-center">
        <svg class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </span>
    <span
        class="absolute z-10 flex h-7 w-7 items-center justify-center rounded-full bg-white dark:bg-gray-900 text-brand-600 dark:text-brand-400 shadow transition-all duration-200"
        :class="$store.theme.resolved === 'dark' ? 'right-1 left-auto' : 'left-1 right-auto'"
    >
        {{-- sun (light) --}}
        <svg x-show="$store.theme.resolved === 'light'" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        {{-- moon (dark) --}}
        <svg x-show="$store.theme.resolved === 'dark'" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
        </svg>
    </span>
</button>
