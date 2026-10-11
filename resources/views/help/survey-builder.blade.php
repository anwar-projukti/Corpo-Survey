<x-app-layout :title="__('surveys.help.title')">
    @php
        $steps = [
            ['icon' => 'plus', 'key' => 'step1'],
            ['icon' => 'squares', 'key' => 'step2', 'callouts' => ['tip', 'reorder']],
            ['icon' => 'list', 'key' => 'step3'],
            ['icon' => 'rocket', 'key' => 'step4', 'callouts' => ['note']],
            ['icon' => 'link', 'key' => 'step5'],
            ['icon' => 'chart', 'key' => 'step6'],
            ['icon' => 'archive', 'key' => 'step7'],
        ];

        $calloutIcon = [
            'tip' => ['color' => 'text-amber-500', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
            'reorder' => ['color' => 'text-gray-400', 'path' => null],
            'note' => ['color' => 'text-brand-500', 'path' => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z'],
        ];

        $icons = [
            'plus' => 'M12 4.5v15m7.5-7.5h-15',
            'squares' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z',
            'list' => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
            'rocket' => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.63 8.41m5.96 5.96a14.926 14.926 0 0 1-5.84 2.58m-5.96-5.96a14.98 14.98 0 0 0-2.58 5.84m2.58-5.84a14.98 14.98 0 0 1 5.96-5.96m-5.96 5.96L9.63 8.41m0 0a6 6 0 0 1 8.4-8.4m-8.4 8.4L5.6 5.4',
            'link' => 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
            'chart' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
            'archive' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375C2.754 3.75 2.25 4.254 2.25 4.875v1.5c0 .621.504 1.125 1.125 1.125Z',
        ];

        $faqs = ['faq1', 'faq2', 'faq3', 'faq4'];
    @endphp

    <div class="max-w-3xl mx-auto py-10 px-4 sm:px-6">
        {{-- Header --}}
        <div class="mb-10">
            <div class="inline-flex items-center gap-1.5 rounded-control bg-brand-50 dark:bg-brand-950 px-2.5 py-1 text-xs font-medium text-brand-700 dark:text-brand-300 mb-4">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
                {{ __('messages.nav.help') }}
            </div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 dark:text-gray-100 text-balance">{{ __('surveys.help.title') }}</h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400">{{ __('surveys.help.intro') }}</p>
        </div>

        {{-- Steps --}}
        <ol class="space-y-3">
            @foreach ($steps as $i => $step)
                <li class="relative rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$step['icon']] }}" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400">
                                {{ __('surveys.help.'.$step['key'].'_eyebrow') }}
                            </p>
                            <h2 class="mt-0.5 font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('surveys.help.'.$step['key'].'_title') }}
                            </h2>
                            <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ __('surveys.help.'.$step['key'].'_body') }}
                            </p>

                            @foreach ($step['callouts'] ?? [] as $callout)
                                <p class="mt-2.5 flex items-start gap-2 text-sm text-gray-500 dark:text-gray-400 bg-surface-subtle dark:bg-gray-800/60 rounded-control px-3 py-2">
                                    @if ($calloutIcon[$callout]['path'])
                                        <svg class="h-4 w-4 mt-0.5 shrink-0 {{ $calloutIcon[$callout]['color'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $calloutIcon[$callout]['path'] }}" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 mt-0.5 shrink-0 {{ $calloutIcon[$callout]['color'] }}" fill="currentColor" viewBox="0 0 20 20"><path d="M7 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm6-10a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2zm0 5a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                    @endif
                                    {{ __('surveys.help.'.$step['key'].'_'.$callout) }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        {{-- FAQ --}}
        <div class="mt-12">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('surveys.help.faq_title') }}</h2>

            <div x-data="{ open: null }" class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 divide-y divide-border-subtle dark:divide-gray-800 overflow-hidden">
                @foreach ($faqs as $i => $faq)
                    <div>
                        <button
                            type="button"
                            @click="open = open === {{ $i }} ? null : {{ $i }}"
                            :aria-expanded="open === {{ $i }}"
                            class="w-full flex items-center justify-between gap-4 px-5 py-3.5 text-left hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                        >
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('surveys.help.'.$faq.'_q') }}</span>
                            <svg
                                class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 transition-transform duration-200"
                                :class="open === {{ $i }} ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak x-transition class="px-5 pb-4 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            {{ __('surveys.help.'.$faq.'_a') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Back to surveys --}}
        @can('create', \App\Models\Survey::class)
            <div class="mt-10 flex justify-center">
                <a href="{{ route('surveys.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                    {{ __('surveys.builder.all_surveys') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </a>
            </div>
        @endcan
    </div>
</x-app-layout>
