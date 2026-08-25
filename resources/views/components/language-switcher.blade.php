@props(['class' => ''])

<div x-data="{ open: false }" @click.outside="open = false" class="relative {{ $class }}">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-1.5 h-9 px-3 rounded-control border border-border-subtle dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
        :aria-expanded="open"
        aria-label="{{ __('messages.nav.settings_language') }}"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9 4.5 4.03 4.5 9-2.015 9-4.5 9ZM3.6 9h16.8M3.6 15h16.8" />
        </svg>
        <span>{{ app()->getLocale() === 'bn' ? __('messages.language.bangla') : __('messages.language.english') }}</span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 mt-2 w-36 rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 shadow-lg py-1 z-50"
    >
        @foreach (\App\Support\Locale::supported() as $locale)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button
                    type="submit"
                    class="flex w-full items-center justify-between px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 {{ app()->getLocale() === $locale ? 'font-medium text-brand-600 dark:text-brand-400' : '' }}"
                >
                    <span>{{ __('messages.language.'.($locale === 'bn' ? 'bangla' : 'english')) }}</span>
                    @if (app()->getLocale() === $locale)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</div>
