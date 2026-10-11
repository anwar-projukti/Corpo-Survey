@props(['label', 'value' => null, 'valueText' => null, 'href' => null, 'color' => 'brand'])

@php
    $colorClasses = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-400',
    ][$color] ?? 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    class="group rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-5 transition {{ $href ? 'hover:-translate-y-0.5 hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-lg hover:shadow-gray-900/5' : '' }}"
>
    <div class="flex items-center justify-between">
        <div class="flex h-10 w-10 items-center justify-center rounded-control {{ $colorClasses }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                {{ $slot }}
            </svg>
        </div>
        @if ($href)
            <svg class="h-4 w-4 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 transition group-hover:opacity-100 group-hover:translate-x-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
            </svg>
        @endif
    </div>
    <p class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100" @if ($valueText) x-text="{{ $valueText }}" @endif>{{ $value }}</p>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
</{{ $tag }}>
