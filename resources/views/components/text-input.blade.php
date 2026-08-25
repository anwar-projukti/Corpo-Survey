@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'mt-1 block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none disabled:opacity-60',
    ]) }}
/>
