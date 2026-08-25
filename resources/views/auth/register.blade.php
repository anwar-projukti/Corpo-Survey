<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Create your organization</h1>

    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="organization_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization name</label>
            <input id="organization_name" type="text" name="organization_name" value="{{ old('organization_name') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('organization_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
            <input id="password" type="password" name="password" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>

        <button type="submit" class="w-full rounded-md bg-gray-900 text-white py-2 dark:bg-gray-100 dark:text-gray-900">
            Register
        </button>
    </form>

    <div class="mt-4 text-sm">
        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 underline">Already have an account?</a>
    </div>
</x-guest-layout>
