@props(['class' => ''])

@php
    $isBangla = app()->getLocale() === 'bn';
@endphp

<form
    method="POST"
    action="{{ route('locale.update') }}"
    class="inline-flex {{ $class }}"
    x-data="localeSwitcher()"
    @submit.prevent="submit($event)"
>
    @csrf
    <input type="hidden" name="locale" value="{{ $isBangla ? 'en' : 'bn' }}">

    <button
        type="submit"
        role="switch"
        aria-checked="{{ $isBangla ? 'true' : 'false' }}"
        aria-label="{{ __('messages.nav.settings_language') }}"
        :disabled="pending"
        :aria-busy="pending"
        class="relative inline-flex h-9 w-20 items-center rounded-full border border-border-subtle dark:border-gray-700 bg-surface-subtle dark:bg-gray-800 transition disabled:opacity-60 disabled:cursor-wait"
    >
        <span class="absolute left-2 text-[11px] font-medium text-gray-500 dark:text-gray-400">EN</span>
        <span class="absolute right-2 text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ __('messages.language.bangla') }}</span>
        {{--
            Positioned with left/right (not a hardcoded translate-x) so the
            1px gap to the pill's border stays correct on both sides no
            matter how wide the active label's text renders — a fixed
            translate-x tuned for "EN" pushed "বাংলা" flush against the
            right edge once its text no longer matched the thumb's
            original width.
        --}}
        <span
            class="absolute z-10 flex h-7 min-w-9 items-center justify-center whitespace-nowrap rounded-full bg-white dark:bg-gray-900 px-1.5 text-[11px] font-semibold text-brand-600 dark:text-brand-400 shadow transition-all duration-200 {{ $isBangla ? 'right-1 left-auto' : 'left-1 right-auto' }}"
        >
            {{ $isBangla ? __('messages.language.bangla') : 'EN' }}
        </span>
    </button>
</form>
