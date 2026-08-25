{{-- Gates the form behind a verified one-time code when the version's
     settings.require_otp is on (spec §5.8). Renders in place of the form
     until otpVerified flips true. --}}
<template x-if="settings.require_otp && !otpVerified">
    <div class="rounded-card border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 p-4 space-y-3">
        <template x-if="!otpSent">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('surveys.otp.destination_label') }}</label>
                <input
                    type="email"
                    x-model="otpDestination"
                    placeholder="{{ __('surveys.otp.destination_placeholder') }}"
                    class="block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
                />
                <button
                    type="button"
                    @click="sendOtp()"
                    :disabled="otpSending || !otpDestination"
                    class="h-10 px-4 rounded-control bg-brand-600 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                >
                    {{ __('surveys.otp.request_button') }}
                </button>
            </div>
        </template>

        <template x-if="otpSent">
            <div class="space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('surveys.otp.sent_confirmation') }}</p>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('surveys.otp.code_label') }}</label>
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    x-model="otpCode"
                    placeholder="{{ __('surveys.otp.code_placeholder') }}"
                    class="block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3 py-2 text-base text-gray-900 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
                />
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="verifyOtp()"
                        :disabled="otpVerifying || !otpCode"
                        class="h-10 px-4 rounded-control bg-brand-600 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                    >
                        {{ __('surveys.otp.verify_button') }}
                    </button>
                    <button
                        type="button"
                        @click="sendOtp()"
                        :disabled="otpSending"
                        class="h-10 px-4 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-60"
                    >
                        {{ __('surveys.otp.resend_button') }}
                    </button>
                </div>
            </div>
        </template>

        <p x-show="otpError" x-text="otpError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>
    </div>
</template>
