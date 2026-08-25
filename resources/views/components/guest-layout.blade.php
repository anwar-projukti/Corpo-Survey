@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <div class="absolute top-4 right-4 flex items-center gap-2">
        <x-language-switcher />
        <x-theme-switcher />
    </div>

    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm bg-surface dark:bg-gray-900 border border-border-subtle dark:border-gray-800 rounded-card p-8">
            <div class="mb-6 text-center">
                <span class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
