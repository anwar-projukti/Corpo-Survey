<x-guest-layout :title="__('messages.auth.confirm_password_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.confirm_password_title') }}</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.confirm_password_body') }}</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="password">{{ __('messages.auth.password') }}</x-input-label>
            <x-text-input id="password" type="password" name="password" required autofocus />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-primary-button>{{ __('messages.actions.confirm') }}</x-primary-button>
    </form>
</x-guest-layout>
