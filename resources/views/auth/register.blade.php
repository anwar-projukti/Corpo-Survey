<x-guest-layout :title="__('messages.auth.register_title')">
    @if ($selectedPlan)
        <div class="mb-4 flex items-center justify-between gap-3 rounded-control border border-brand-200 dark:border-brand-800 bg-brand-50 dark:bg-brand-950 px-3.5 py-2.5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400">{{ __('messages.auth.selected_plan') }}</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $selectedPlan->text('name', app()->getLocale()) }}
                    <span class="font-normal text-gray-500 dark:text-gray-400">&middot; {{ $selectedPlan->text('price', app()->getLocale()) }}</span>
                </p>
                @if ($selectedPlan->hasTrial())
                    <p class="mt-0.5 text-xs font-medium text-green-600 dark:text-green-400">
                        {{ trans_choice('messages.landing.pricing_trial_days', $selectedPlan->trial_days, ['count' => $selectedPlan->trial_days]) }}
                    </p>
                @endif
            </div>
            <a href="{{ url('/#pricing') }}" class="shrink-0 text-xs font-medium text-brand-600 dark:text-brand-400 hover:underline">
                {{ __('messages.auth.change_plan') }}
            </a>
        </div>
    @endif

    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.register_title') }}</h1>

    <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
        @csrf
        @if ($selectedPlan)
            <input type="hidden" name="plan" value="{{ $selectedPlan->id }}">
        @endif

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

    <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('messages.auth.already_have_account') }}
        <a href="{{ route('login') }}" class="font-medium text-brand-600 dark:text-brand-400 hover:underline">{{ __('messages.auth.login_link') }}</a>
    </div>
</x-guest-layout>
