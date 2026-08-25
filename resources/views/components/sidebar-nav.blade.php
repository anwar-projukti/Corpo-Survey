@props(['class' => ''])

@php
    // Spec §13. Each entry: label key, route name (or null for a
    // not-yet-built destination), and icon. Sub-items are indented.
    // Routes that don't exist yet resolve to '#' rather than throwing,
    // since most of this nav targets screens later phases will build.
    $sections = [
        [
            'label' => 'nav.dashboard',
            'route' => 'home',
            'children' => [],
        ],
        [
            'label' => 'nav.surveys',
            'route' => null,
            'children' => ['nav.all_surveys', 'nav.create_survey', 'nav.templates', 'nav.question_library'],
        ],
        [
            'label' => 'nav.responses',
            'route' => null,
            'children' => ['nav.all_responses', 'nav.review', 'nav.export_history'],
        ],
        [
            'label' => 'nav.analytics',
            'route' => null,
            'children' => ['nav.survey_analytics', 'nav.reports'],
        ],
        [
            'label' => 'nav.respondents',
            'route' => null,
            'children' => [],
        ],
        [
            'label' => 'nav.api',
            'route' => null,
            'children' => ['nav.api_keys', 'nav.documentation', 'nav.webhooks'],
        ],
        [
            'label' => 'nav.users_and_permissions',
            'route' => null,
            'children' => [],
        ],
        [
            'label' => 'nav.audit_logs',
            'route' => null,
            'children' => [],
        ],
        [
            'label' => 'nav.settings',
            'route' => null,
            'children' => [
                'nav.settings_general',
                'nav.settings_language',
                'nav.settings_appearance',
                'nav.settings_security',
                'nav.settings_notifications',
                'nav.settings_api',
            ],
        ],
    ];
@endphp

<aside class="{{ $class }} w-64 shrink-0 flex-col border-r border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
    <div class="h-16 flex items-center px-6 border-b border-border-subtle dark:border-gray-800">
        <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($sections as $section)
            @php
                $routeExists = $section['route'] && \Illuminate\Support\Facades\Route::has($section['route']);
                $href = $routeExists ? route($section['route']) : '#';
                $isActive = $routeExists && request()->routeIs($section['route']);
            @endphp

            <a
                href="{{ $href }}"
                class="flex items-center rounded-control px-3 py-2 text-sm font-medium transition
                    {{ $isActive
                        ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800' }}"
            >
                {{ __('messages.'.$section['label']) }}
            </a>

            @if (count($section['children']))
                <div class="ml-3 pl-3 border-l border-border-subtle dark:border-gray-800 space-y-0.5">
                    @foreach ($section['children'] as $child)
                        <a
                            href="#"
                            class="block rounded-control px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100 transition"
                        >
                            {{ __('messages.'.$child) }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
