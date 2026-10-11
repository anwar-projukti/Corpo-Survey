@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-brand-600 focus:text-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium">
        {{ __('messages.actions.skip_to_content') }}
    </a>

    <div class="flex flex-col min-h-screen">
        <x-impersonation-banner />

        <div class="flex flex-1 min-h-0">
            <x-sidebar-nav class="hidden lg:flex" />

            <div class="flex-1 flex flex-col min-w-0">
                <header class="h-16 shrink-0 flex items-center justify-between gap-4 px-4 sm:px-6 border-b border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                    <div class="flex items-center gap-2 lg:hidden">
                        <x-app-logo class="h-6 w-6" />
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
                    </div>

                    <div class="flex-1"></div>

                    <div class="flex items-center gap-2">
                        <x-language-switcher />
                        <x-theme-switcher />

                        @auth
                            <x-profile-menu />
                        @endauth
                    </div>
                </header>

                <main id="main-content" tabindex="-1" class="flex-1 min-w-0 focus:outline-none">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</body>
</html>
