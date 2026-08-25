<x-app-layout>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">The dashboard shell arrives in Phase 2.</p>
    </div>
</x-app-layout>
