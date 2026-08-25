<x-guest-layout :title="__('messages.auth.two_factor_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.two_factor_title') }}</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.two_factor_body') }}</p>

    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="code">{{ __('messages.auth.code') }}</x-input-label>
            <x-text-input id="code" type="text" name="code" inputmode="numeric" autofocus />
            <x-input-error :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="recovery_code">{{ __('messages.auth.recovery_code') }}</x-input-label>
            <x-text-input id="recovery_code" type="text" name="recovery_code" />
            <x-input-error :messages="$errors->get('recovery_code')" />
        </div>

        <x-primary-button>{{ __('messages.auth.verify') }}</x-primary-button>
    </form>
</x-guest-layout>
