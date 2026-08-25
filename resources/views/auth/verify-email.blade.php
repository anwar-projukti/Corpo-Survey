<x-guest-layout>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Verify your email</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        We sent a verification link to your email address. Click it to continue.
    </p>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-4 text-sm text-green-600">A new verification link has been sent.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <button type="submit" class="rounded-md bg-gray-900 text-white px-4 py-2 dark:bg-gray-100 dark:text-gray-900">
            Resend verification email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 underline">Log out</button>
    </form>
</x-guest-layout>
