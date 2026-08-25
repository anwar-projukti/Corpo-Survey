<x-app-layout :title="__('messages.nav.dashboard')">
    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.welcome', ['name' => auth()->user()->name]) }}</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ config('app.name') }}</p>
    </div>
</x-app-layout>
