<x-guest-layout :title="__('messages.auth.reset_password_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.reset_password_title') }}</h1>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email">{{ __('messages.auth.email') }}</x-input-label>
            <x-text-input id="email" type="email" name="email" value="{{ old('email', $request->query('email')) }}" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password">{{ __('messages.auth.new_password') }}</x-input-label>
            <x-text-input id="password" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation">{{ __('messages.auth.confirm_new_password') }}</x-input-label>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required />
        </div>

        <x-primary-button>{{ __('messages.auth.reset_password_submit') }}</x-primary-button>
    </form>
</x-guest-layout>
