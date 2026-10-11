<x-app-layout :title="__('pricing_plans.title')">
    @php
        $pricingPlansI18n = [
            'load_failed' => __('pricing_plans.load_failed'),
            'save_failed' => __('pricing_plans.save_failed'),
            'delete_title' => __('pricing_plans.delete_title'),
            'delete_confirm' => __('pricing_plans.delete_confirm'),
        ];
    @endphp
    <script>
        window.__i18n = @json($pricingPlansI18n);
    </script>

    <div x-data="pricingPlanManagement()" x-init="init()" class="max-w-4xl mx-auto py-8 px-4 sm:px-6">
        <div class="flex items-center justify-between gap-4 mb-2">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ __('pricing_plans.title') }}</h1>
            <button type="button" @click="openCreateForm()" class="shrink-0 rounded-control bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-3 py-1.5 transition">
                {{ __('pricing_plans.add') }}
            </button>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('pricing_plans.body') }}</p>

        <div x-show="loading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

        <template x-if="!loading && !loadError && plans.length === 0">
            <div class="text-center py-16">
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ __('pricing_plans.none_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('pricing_plans.none_body') }}</p>
            </div>
        </template>

        <div
            x-show="!loading && plans.length > 0"
            x-sortable="(orderedIds) => reorder(orderedIds)"
            class="space-y-3"
        >
            <template x-for="plan in plans" :key="plan.id">
                <div
                    x-sortable-item="plan.id"
                    class="flex items-center gap-3 rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-4 cursor-move"
                >
                    <svg class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                    </svg>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 dark:text-gray-100" x-text="plan.name_en"></span>
                            <span x-show="plan.highlighted" x-cloak class="inline-flex items-center rounded-full bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300 text-xs font-medium px-2 py-0.5">
                                {{ __('pricing_plans.highlighted_badge') }}
                            </span>
                            <span x-show="plan.trial_days > 0" x-cloak class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-300 text-xs font-medium px-2 py-0.5">
                                <span x-text="plan.trial_days"></span>&nbsp;{{ __('pricing_plans.trial_days_badge') }}
                            </span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="plan.price_en"></span>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" @click="openEditForm(plan)" class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 hover:bg-surface-subtle dark:hover:bg-gray-800 transition" title="{{ __('pricing_plans.edit') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                        </button>
                        <button type="button" @click="confirmDelete(plan)" class="p-1.5 rounded-control text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-surface-subtle dark:hover:bg-gray-800 transition" title="{{ __('pricing_plans.delete') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Add/edit plan modal --}}
        <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-8 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeForm()"></div>

            <div class="relative w-full max-w-lg rounded-2xl border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-2xl p-6 my-auto">
                <div class="space-y-4">
                    <x-landing-content.field label="{{ __('pricing_plans.field_name') }}" model-en="form.name_en" model-bn="form.name_bn" />

                    <div>
                        <x-landing-content.field label="{{ __('pricing_plans.field_price') }}" model-en="form.price_en" model-bn="form.price_bn" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('pricing_plans.field_price_help') }}</p>
                    </div>

                    <x-landing-content.field label="{{ __('pricing_plans.field_description') }}" model-en="form.description_en" model-bn="form.description_bn" textarea />

                    <div>
                        <x-landing-content.field label="{{ __('pricing_plans.field_features') }}" model-en="form.features_en_text" model-bn="form.features_bn_text" textarea />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('pricing_plans.field_features_help') }}</p>
                    </div>

                    <x-landing-content.field label="{{ __('pricing_plans.field_button_text') }}" model-en="form.button_text_en" model-bn="form.button_text_bn" />

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('pricing_plans.field_button_target') }}</label>
                            <select x-model="form.button_target" class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5">
                                <option value="register">{{ __('pricing_plans.button_target_register') }}</option>
                                <option value="contact">{{ __('pricing_plans.button_target_contact') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('pricing_plans.field_trial_days') }}</label>
                            <input
                                type="number"
                                min="0"
                                max="365"
                                x-model="form.trial_days"
                                placeholder="{{ __('pricing_plans.field_trial_days_placeholder') }}"
                                class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                            >
                        </div>
                    </div>
                    <p class="-mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('pricing_plans.field_trial_days_help') }}</p>

                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="form.highlighted" class="rounded border-border-subtle dark:border-gray-700 text-brand-600 focus:ring-brand-500">
                        {{ __('pricing_plans.field_highlighted') }}
                    </label>

                    <p x-show="submitError" x-text="submitError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>

                    <div class="flex items-center gap-2 pt-2">
                        <button
                            type="button"
                            :disabled="submitting"
                            @click="submit()"
                            class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 transition"
                        >
                            <span x-show="!submitting">{{ __('pricing_plans.save') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('pricing_plans.saving') }}</span>
                        </button>
                        <button type="button" @click="closeForm()" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                            {{ __('pricing_plans.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete confirmation modal --}}
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4" @keydown.escape.window="!confirmBusy && cancelConfirm()">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="!confirmBusy && cancelConfirm()"></div>

            <div role="alertdialog" aria-modal="true" :aria-label="confirmTitle" class="relative w-full max-w-sm rounded-2xl border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 shadow-2xl ring-1 ring-black/5 overflow-hidden">
                <div class="px-6 pt-6 pb-5">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-center text-base font-semibold text-gray-900 dark:text-gray-100" x-text="confirmTitle"></h2>
                    <p class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400" x-text="confirmBody"></p>
                </div>
                <div class="flex border-t border-border-subtle dark:border-gray-800">
                    <button type="button" :disabled="confirmBusy" @click="cancelConfirm()" class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 disabled:opacity-50 transition border-r border-border-subtle dark:border-gray-800">
                        {{ __('messages.actions.cancel') }}
                    </button>
                    <button type="button" :disabled="confirmBusy" @click="runConfirm()" class="flex-1 px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950 transition disabled:opacity-60">
                        <span x-show="!confirmBusy">{{ __('messages.actions.delete') }}</span>
                        <span x-show="confirmBusy" x-cloak>{{ __('messages.system.loading') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
