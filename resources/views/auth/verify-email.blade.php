<x-guest-layout :title="__('messages.auth.verify_email_title')">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.verify_email_title') }}</h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.verify_email_body') }}</p>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-4 text-sm text-green-600 dark:text-green-400">{{ __('messages.auth.verify_email_resent') }}</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-primary-button class="w-auto px-4">{{ __('messages.auth.resend_verification') }}</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:text-brand-600 dark:hover:text-brand-400">{{ __('messages.actions.log_out') }}</button>
    </form>
</x-guest-layout>
