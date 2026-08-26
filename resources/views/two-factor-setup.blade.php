<x-app-layout :title="__('messages.auth.two_factor_required_title')">
    @php
        $twoFactorI18n = [
            'invalid_code' => __('messages.auth.two_factor_invalid_code'),
            'generic_error' => __('messages.auth.two_factor_generic_error'),
            'confirm_password_url' => route('two-factor.setup.confirm-password'),
            'dashboard_url' => route('home'),
        ];
    @endphp
    <script>
        window.__i18n = @json($twoFactorI18n);
    </script>

    <div x-data="twoFactorSetup()" x-init="init()" class="max-w-xl mx-auto py-12 px-4 sm:px-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.auth.two_factor_required_title') }}</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('messages.auth.two_factor_required_body') }}</p>

        <p x-show="error" x-text="error" x-cloak class="mt-4 text-sm text-red-600 dark:text-red-400"></p>

        <div x-show="step === 'enable'" class="mt-8">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.two_factor_setup_intro') }}</p>
            <button
                type="button"
                @click="enable()"
                :disabled="working"
                class="mt-4 rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 transition disabled:opacity-60"
            >
                {{ __('messages.auth.two_factor_enable_button') }}
            </button>
        </div>

        <div x-show="step === 'confirm'" x-cloak class="mt-8 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.two_factor_scan_instructions') }}</p>

            {{-- Fortify's generated SVG has fixed width="192" height="192"
                 attributes baked in (Laravel\Fortify\TwoFactorAuthenticatable::twoFactorQrCodeSvg())
                 — too small to focus a phone camera on from typical
                 screen-viewing distance. [&>svg]:!w-full overrides those
                 inline attributes via Tailwind's arbitrary-variant selector
                 rather than needing to touch the SVG markup itself. --}}
            <div class="inline-block rounded-card border border-border-subtle dark:border-gray-700 bg-white p-4 [&>svg]:!w-full [&>svg]:!h-auto max-w-[280px]" x-html="qrCodeSvg"></div>

            <details class="text-sm text-gray-600 dark:text-gray-400">
                <summary class="cursor-pointer">{{ __('messages.auth.two_factor_manual_setup') }}</summary>
                <p class="mt-2 break-all font-mono text-xs" x-text="manualSetupUrl"></p>
            </details>

            <div>
                <x-input-label for="confirmation_code">{{ __('messages.auth.code') }}</x-input-label>
                <x-text-input
                    id="confirmation_code"
                    type="text"
                    inputmode="numeric"
                    autofocus
                    x-model="confirmationCode"
                    @keydown.enter="confirm()"
                />
            </div>

            <button
                type="button"
                @click="confirm()"
                :disabled="working || !confirmationCode"
                class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 transition disabled:opacity-60"
            >
                {{ __('messages.auth.verify') }}
            </button>
        </div>

        <div x-show="step === 'recovery'" x-cloak class="mt-8 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.auth.two_factor_recovery_intro') }}</p>

            <ul class="rounded-card border border-border-subtle dark:border-gray-700 bg-surface-subtle dark:bg-gray-900 p-4 font-mono text-sm space-y-1">
                <template x-for="recoveryCode in recoveryCodes" :key="recoveryCode">
                    <li x-text="recoveryCode" class="text-gray-900 dark:text-gray-100"></li>
                </template>
            </ul>

            <button
                type="button"
                @click="finish()"
                class="rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 transition"
            >
                {{ __('messages.auth.two_factor_continue_to_dashboard') }}
            </button>
        </div>
    </div>
</x-app-layout>
