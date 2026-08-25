<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Two-factor authentication</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Enter the code from your authenticator app, or one of your recovery codes.
    </p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Or a recovery code</label>
            <input id="recovery_code" type="text" name="recovery_code"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('recovery_code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 text-white py-2 dark:bg-gray-100 dark:text-gray-900">
            Verify
        </button>
    </form>
</x-guest-layout>
