<x-guest-layout :title="__('messages.auth.register_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.register_title') }}</h1>

    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="organization_name">{{ __('messages.auth.organization_name') }}</x-input-label>
            <x-text-input id="organization_name" type="text" name="organization_name" value="{{ old('organization_name') }}" required autofocus />
            <x-input-error :messages="$errors->get('organization_name')" />
        </div>

        <div>
            <x-input-label for="name">{{ __('messages.auth.your_name') }}</x-input-label>
            <x-text-input id="name" type="text" name="name" value="{{ old('name') }}" required />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email">{{ __('messages.auth.email') }}</x-input-label>
            <x-text-input id="email" type="email" name="email" value="{{ old('email') }}" required />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password">{{ __('messages.auth.password') }}</x-input-label>
            <x-text-input id="password" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation">{{ __('messages.auth.confirm_password') }}</x-input-label>
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button>{{ __('messages.actions.register') }}</x-primary-button>
    </form>

    <div class="mt-4 text-sm">
        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400">{{ __('messages.auth.already_have_account') }}</a>
    </div>
</x-guest-layout>
