<x-guest-layout :title="__('messages.auth.forgot_password_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.forgot_password_title') }}</h1>

    @if (session('status'))
        <div class="mt-4 text-sm text-green-600 dark:text-green-400">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email">{{ __('messages.auth.email') }}</x-input-label>
            <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-primary-button>{{ __('messages.auth.email_reset_link') }}</x-primary-button>
    </form>
</x-guest-layout>
