@props(['class' => ''])

@php
    // Spec §13. Each entry: label key, route name (or null for a
    // not-yet-built destination), and children (each itself a
    // label/route pair). Routes that don't exist yet resolve to '#'
    // rather than throwing, since most of this nav targets screens
    // later phases will build.
    $sections = [
        [
            'label' => 'nav.dashboard',
            'route' => 'home',
            'children' => [],
        ],
        [
            'label' => 'nav.surveys',
            'route' => 'surveys.index',
            'children' => [
                ['label' => 'nav.all_surveys', 'route' => 'surveys.index'],
                ['label' => 'nav.templates', 'route' => null],
                ['label' => 'nav.question_library', 'route' => null],
            ],
        ],
        [
            'label' => 'nav.responses',
            'route' => null,
            'children' => [
                ['label' => 'nav.all_responses', 'route' => null],
                ['label' => 'nav.review', 'route' => null],
                ['label' => 'nav.export_history', 'route' => null],
            ],
        ],
        [
            'label' => 'nav.analytics',
            'route' => null,
            'children' => [
                ['label' => 'nav.survey_analytics', 'route' => null],
                ['label' => 'nav.reports', 'route' => null],
            ],
        ],
        [
            'label' => 'nav.respondents',
            'route' => null,
            'children' => [],
        ],
        [
            'label' => 'nav.api',
            'route' => null,
            'children' => [
                ['label' => 'nav.api_keys', 'route' => null],
                ['label' => 'nav.documentation', 'route' => null],
                ['label' => 'nav.webhooks', 'route' => null],
            ],
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
                ['label' => 'nav.settings_general', 'route' => null],
                ['label' => 'nav.settings_language', 'route' => null],
                ['label' => 'nav.settings_appearance', 'route' => null],
                ['label' => 'nav.settings_security', 'route' => null],
                ['label' => 'nav.settings_notifications', 'route' => null],
                ['label' => 'nav.settings_api', 'route' => null],
            ],
        ],
    ];

    $linkTarget = function (?string $routeName) {
        $exists = $routeName && \Illuminate\Support\Facades\Route::has($routeName);

        return [
            'href' => $exists ? route($routeName) : '#',
            'active' => $exists && request()->routeIs($routeName),
        ];
    };
@endphp

<aside class="{{ $class }} w-64 shrink-0 flex-col border-r border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
    <div class="h-16 flex items-center px-6 border-b border-border-subtle dark:border-gray-800">
        <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($sections as $section)
            @php($target = $linkTarget($section['route']))

            <a
                href="{{ $target['href'] }}"
                class="flex items-center rounded-control px-3 py-2 text-sm font-medium transition
                    {{ $target['active']
                        ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800' }}"
            >
                {{ __('messages.'.$section['label']) }}
            </a>

            @if (count($section['children']))
                <div class="ml-3 pl-3 border-l border-border-subtle dark:border-gray-800 space-y-0.5">
                    @foreach ($section['children'] as $child)
                        @php($childTarget = $linkTarget($child['route']))
                        <a
                            href="{{ $childTarget['href'] }}"
                            class="block rounded-control px-3 py-1.5 text-sm transition
                                {{ $childTarget['active']
                                    ? 'text-brand-600 dark:text-brand-400 font-medium'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100' }}"
                        >
                            {{ __('messages.'.$child['label']) }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
