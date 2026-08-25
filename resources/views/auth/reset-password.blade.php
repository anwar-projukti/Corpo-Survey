<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Reset your password</h1>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->query('email')) }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New password</label>
            <input id="password" type="password" name="password" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 text-white py-2 dark:bg-gray-100 dark:text-gray-900">
            Reset password
        </button>
    </form>
</x-guest-layout>
