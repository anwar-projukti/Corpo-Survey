<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Forgot your password?</h1>

    @if (session('status'))
        <div class="mt-4 text-sm text-green-600">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 text-white py-2 dark:bg-gray-100 dark:text-gray-900">
            Email password reset link
        </button>
    </form>
</x-guest-layout>
