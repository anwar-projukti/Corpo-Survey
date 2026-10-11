<x-app-layout :title="__('messages.nav.dashboard')">
    <div class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-brand-600 dark:text-brand-400">{{ now()->translatedFormat('l, j F Y') }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">
                    {{ __('messages.welcome', ['name' => auth()->user()->name]) }}
                </h1>
            </div>

            @unless ($isSystemAdmin)
                <a
                    href="{{ route('surveys.index') }}"
                    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-control bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 hover:shadow-md"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('surveys.builder.create_survey') }}
                </a>
            @endunless
        </div>

        @if ($isSystemAdmin)
            <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-dashboard.stat-tile
                    :href="route('organizations.index')"
                    :label="__('messages.onboarding.stat_organizations')"
                    :value="$organizationCount"
                    color="brand"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.onboarding.stat_users')"
                    :value="$userCount"
                    color="violet"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.onboarding.stat_surveys')"
                    :value="$surveyCount"
                    color="amber"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6a1.5 1.5 0 0 0-1.5 1.5v16.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06l-3.622-3.622a1.5 1.5 0 0 0-1.06-.44Z" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.onboarding.stat_responses')"
                    :value="$responseCount"
                    color="emerald"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </x-dashboard.stat-tile>
            </div>

            <div class="mt-8 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-border-subtle dark:border-gray-800 px-5 py-4">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.dashboard.recent_organizations') }}</h2>
                    <a href="{{ route('organizations.index') }}" class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                        {{ __('messages.dashboard.view_all') }}
                    </a>
                </div>

                @if ($recentOrganizations->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('messages.dashboard.no_organizations') }}</p>
                @else
                    <ul class="divide-y divide-border-subtle dark:divide-gray-800">
                        @foreach ($recentOrganizations as $organization)
                            <li>
                                <a href="{{ route('organizations.index') }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                                        {{ mb_strtoupper(mb_substr($organization->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $organization->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ trans_choice('messages.dashboard.survey_count', $organization->surveys_count, ['count' => $organization->surveys_count]) }}
                                            &middot;
                                            {{ trans_choice('messages.dashboard.user_count', $organization->users_count, ['count' => $organization->users_count]) }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-sm text-gray-400 dark:text-gray-500">{{ $organization->created_at->diffForHumans() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @else
            <div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <x-dashboard.stat-tile
                    :href="route('surveys.index')"
                    :label="__('messages.onboarding.stat_surveys')"
                    :value="$surveyCount"
                    color="brand"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6a1.5 1.5 0 0 0-1.5 1.5v16.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06l-3.622-3.622a1.5 1.5 0 0 0-1.06-.44Z" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.dashboard.stat_published')"
                    :value="$publishedCount"
                    color="emerald"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.onboarding.stat_responses')"
                    :value="$responseCount"
                    color="amber"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </x-dashboard.stat-tile>
                <x-dashboard.stat-tile
                    :label="__('messages.dashboard.stat_field_users')"
                    :value="$fieldUserCount"
                    color="violet"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </x-dashboard.stat-tile>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-border-subtle dark:border-gray-800 px-5 py-4">
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.dashboard.recent_surveys') }}</h2>
                        <a href="{{ route('surveys.index') }}" class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                            {{ __('messages.dashboard.view_all') }}
                        </a>
                    </div>

                    @if ($recentSurveys->isEmpty())
                        <div class="px-5 py-12 text-center">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('messages.dashboard.no_surveys_title') }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('messages.dashboard.no_surveys_body') }}</p>
                            <a href="{{ route('surveys.index') }}" class="mt-4 inline-flex h-9 items-center rounded-control bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700 transition">
                                {{ __('surveys.builder.create_survey') }}
                            </a>
                        </div>
                    @else
                        <ul class="divide-y divide-border-subtle dark:divide-gray-800">
                            @foreach ($recentSurveys as $survey)
                                <li>
                                    <a href="{{ route('surveys.show', $survey) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ $survey->title }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ trans_choice('messages.dashboard.response_count', $survey->responses_count, ['count' => $survey->responses_count]) }}
                                                &middot;
                                                {{ $survey->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <span
                                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $survey->isPublished()
                                                ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}"
                                        >
                                            {{ $survey->isPublished() ? __('surveys.status.published') : __('surveys.status.draft') }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-5">
                    @if ($isOnboarding)
                        <h2 class="font-medium text-gray-900 dark:text-gray-100 mb-1">{{ __('messages.onboarding.title') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('messages.onboarding.body') }}</p>

                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $checklist['has_survey'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}" aria-hidden="true">
                                    @if ($checklist['has_survey'])
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium {{ $checklist['has_survey'] ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ __('messages.onboarding.step_create_survey') }}
                                    </p>
                                    @unless ($checklist['has_survey'])
                                        <a href="{{ route('surveys.index') }}" class="text-sm text-brand-600 dark:text-brand-400 hover:underline">
                                            {{ __('surveys.builder.create_survey') }} &rarr;
                                        </a>
                                    @endunless
                                </div>
                            </li>

                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $checklist['has_published_survey'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}" aria-hidden="true">
                                    @if ($checklist['has_published_survey'])
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium {{ $checklist['has_published_survey'] ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ __('messages.onboarding.step_publish_survey') }}
                                    </p>
                                    @unless ($checklist['has_published_survey'])
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.onboarding.step_publish_survey_body') }}</p>
                                    @endunless
                                </div>
                            </li>

                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $checklist['has_field_user'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}" aria-hidden="true">
                                    @if ($checklist['has_field_user'])
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium {{ $checklist['has_field_user'] ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ __('messages.onboarding.step_invite_field_user') }}
                                    </p>
                                    @unless ($checklist['has_field_user'])
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.onboarding.step_invite_field_user_body') }}</p>
                                    @endunless
                                </div>
                            </li>

                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $checklist['has_response'] ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}" aria-hidden="true">
                                    @if ($checklist['has_response'])
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium {{ $checklist['has_response'] ? 'text-gray-500 dark:text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ __('messages.onboarding.step_first_response') }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    @else
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('messages.dashboard.quick_actions') }}</h2>
                        <div class="space-y-1">
                            <a href="{{ route('surveys.index') }}" class="flex items-center gap-3 rounded-control px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                {{ __('surveys.builder.create_survey') }}
                            </a>
                            <a href="{{ route('export-history.index') }}" class="flex items-center gap-3 rounded-control px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ __('messages.nav.export_history') }}
                            </a>
                            <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-control px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                {{ __('messages.nav.users_and_permissions') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
