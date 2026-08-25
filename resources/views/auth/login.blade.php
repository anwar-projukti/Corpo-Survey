<x-guest-layout :title="__('messages.auth.login_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.login_title') }}</h1>

    @if (session('status'))
        <div class="mt-4 text-sm text-green-600 dark:text-green-400">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email">{{ __('messages.auth.email') }}</x-input-label>
            <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password">{{ __('messages.auth.password') }}</x-input-label>
            <x-text-input id="password" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center">
            <input id="remember" type="checkbox" name="remember" class="rounded border-border-subtle dark:border-gray-700 text-brand-600 focus:ring-brand-500">
            <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.remember_me') }}</label>
        </div>

        <x-primary-button>{{ __('messages.actions.log_in') }}</x-primary-button>
    </form>

    <div class="mt-4 flex justify-between text-sm">
        <a href="{{ route('password.request') }}" class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400">{{ __('messages.auth.forgot_password') }}</a>
        <a href="{{ route('register') }}" class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400">{{ __('messages.auth.create_account') }}</a>
    </div>
</x-guest-layout>
