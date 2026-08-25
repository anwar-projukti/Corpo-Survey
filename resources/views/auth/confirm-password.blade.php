<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Confirm your password</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">This is a sensitive action — please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input id="password" type="password" name="password" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 text-white py-2 dark:bg-gray-100 dark:text-gray-900">
            Confirm
        </button>
    </form>
</x-guest-layout>
