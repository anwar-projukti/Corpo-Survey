@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <div class="flex min-h-screen">
        <x-sidebar-nav class="hidden lg:flex" />

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 shrink-0 flex items-center justify-between gap-4 px-4 sm:px-6 border-b border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
                <div class="flex items-center gap-2 lg:hidden">
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
                </div>

                <div class="flex-1"></div>

                <div class="flex items-center gap-2">
                    <x-language-switcher />
                    <x-theme-switcher />

                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="h-9 px-3 rounded-control text-sm text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                {{ __('messages.actions.log_out') }}
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <main class="flex-1 min-w-0">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
