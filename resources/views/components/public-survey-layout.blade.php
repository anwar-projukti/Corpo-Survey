@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head', ['title' => $title, 'entry' => 'resources/js/public.js'])
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen">
    {{-- A faint radial wash behind the header only, in the brand hue — just
         enough to lift the page off flat gray without competing with the
         form itself, which is where attention needs to stay. --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,theme(colors.brand.100),transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,theme(colors.brand.950),transparent)] opacity-60"></div>

    <header class="relative flex items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>

        <div class="flex items-center gap-2">
            <x-language-switcher />
            <x-theme-switcher />
        </div>
    </header>

    <main class="relative px-4 pb-20 pt-4 sm:px-6">
        <div class="w-full max-w-2xl mx-auto">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
