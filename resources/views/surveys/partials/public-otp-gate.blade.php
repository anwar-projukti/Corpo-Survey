{{-- Gates the form behind a verified one-time code when the version's
     settings.require_otp is on (spec §5.8). Renders in place of the form
     until otpVerified flips true. --}}
<template x-if="settings.require_otp && !otpVerified">
    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-sm p-6 sm:p-8 space-y-4">
        <div class="flex items-start gap-3">
            <div class="shrink-0 flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
            <p class="pt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.otp.gate_intro') }}</p>
        </div>

        <template x-if="!otpSent">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('surveys.otp.destination_label') }}</label>
                    <input
                        type="email"
                        x-model="otpDestination"
                        placeholder="{{ __('surveys.otp.destination_placeholder') }}"
                        class="block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3.5 py-2.5 text-base text-gray-900 dark:text-gray-100 shadow-sm placeholder:text-xs placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500 transition"
                    />
                </div>
                <button
                    type="button"
                    @click="sendOtp()"
                    :disabled="otpSending || !otpDestination"
                    class="inline-flex items-center gap-2 h-10 px-4 rounded-control bg-brand-600 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60 transition"
                >
                    <svg x-show="otpSending" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9V0c6.627 0 12 5.373 12 12h-3Z"></path>
                    </svg>
                    {{ __('surveys.otp.request_button') }}
                </button>
            </div>
        </template>

        <template x-if="otpSent">
            <div class="space-y-3">
                <p class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950 rounded-control px-3 py-2">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ __('surveys.otp.sent_confirmation') }}
                </p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('surveys.otp.code_label') }}</label>
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        x-model="otpCode"
                        placeholder="{{ __('surveys.otp.code_placeholder') }}"
                        class="block w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 px-3.5 py-2.5 text-lg tracking-[0.3em] text-center font-medium text-gray-900 dark:text-gray-100 shadow-sm placeholder:text-sm placeholder:tracking-normal placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-4 focus:ring-brand-500/15 focus:border-brand-500 transition"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="verifyOtp()"
                        :disabled="otpVerifying || !otpCode"
                        class="inline-flex items-center gap-2 h-10 px-4 rounded-control bg-brand-600 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60 transition"
                    >
                        <svg x-show="otpVerifying" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9V0c6.627 0 12 5.373 12 12h-3Z"></path>
                        </svg>
                        {{ __('surveys.otp.verify_button') }}
                    </button>
                    <button
                        type="button"
                        @click="sendOtp()"
                        :disabled="otpSending"
                        class="h-10 px-4 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-60 transition"
                    >
                        {{ __('surveys.otp.resend_button') }}
                    </button>
                </div>
            </div>
        </template>

        <p x-show="otpError" x-text="otpError" x-cloak class="flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400"></p>
    </div>
</template>
