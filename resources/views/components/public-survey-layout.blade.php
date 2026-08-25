@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen">
    <header class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>

        <div class="flex items-center gap-2">
            <x-language-switcher />
            <x-theme-switcher />
        </div>
    </header>

    <main class="px-4 pb-16 sm:px-6">
        <div class="w-full max-w-xl mx-auto">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
