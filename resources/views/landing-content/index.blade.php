<x-app-layout :title="__('landing_content.title')">
    @php
        $landingContentI18n = [
            'load_failed' => __('landing_content.load_failed'),
            'save_failed' => __('landing_content.save_failed'),
            'logo_upload_failed' => __('landing_content.logo_upload_failed'),
        ];
    @endphp
    <script>
        window.__i18n = @json($landingContentI18n);
    </script>

    <div
        x-data="{ ...landingContentEditor(), activeTab: 'branding' }"
        x-init="init()"
        class="flex h-[calc(100vh-4rem)] flex-col"
    >
        <div class="flex items-start justify-between gap-4 shrink-0 border-b border-border-subtle dark:border-gray-800 px-4 py-4 sm:px-6">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.title') }}</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.body') }}</p>
            </div>
            <a
                href="{{ url('/') }}"
                target="_blank"
                rel="noopener"
                class="shrink-0 inline-flex items-center gap-1.5 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
            >
                {{ __('landing_content.view_live') }}
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 6.75 17.25M17.25 6.75H8.25M17.25 6.75v9" />
                </svg>
            </a>
        </div>

        <div x-show="loading" class="p-6 text-sm text-gray-500 dark:text-gray-400">{{ __('messages.system.loading') }}</div>
        <p x-show="loadError" x-text="loadError" x-cloak class="p-6 text-sm text-red-600 dark:text-red-400"></p>

        <form x-show="!loading && !loadError" x-cloak @submit.prevent="save()" class="flex flex-1 min-h-0">
            {{-- Section nav — stays put; only the right panel scrolls, so
                 jumping between sections never costs a scroll back to the
                 top of one long page. --}}
            <nav class="w-56 shrink-0 overflow-y-auto border-r border-border-subtle dark:border-gray-800 p-3">
                @foreach ([
                    'branding' => 'section_branding',
                    'hero' => 'section_hero',
                    'features' => 'section_features',
                    'pricing' => 'section_pricing',
                    'about' => 'section_about',
                    'contact' => 'section_contact',
                ] as $tab => $labelKey)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab }}'"
                        :class="activeTab === '{{ $tab }}'
                            ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800'"
                        class="block w-full rounded-control px-3 py-2 text-left text-sm font-medium transition"
                    >
                        {{ __('landing_content.'.$labelKey) }}
                    </button>
                @endforeach
            </nav>

            <div class="flex-1 min-w-0 overflow-y-auto p-4 sm:p-6">
                {{-- Branding --}}
                <section x-show="activeTab === 'branding'" x-cloak class="max-w-2xl">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_branding') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_branding_body') }}</p>

                    <div class="mt-4 flex items-center gap-4">
                        <img :src="logoUrl" alt="" class="h-16 w-16 rounded-control border border-border-subtle dark:border-gray-700 object-contain bg-surface-subtle dark:bg-gray-800">

                        <div>
                            <div class="flex items-center gap-2">
                                <label class="cursor-pointer rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition">
                                    <span x-show="!uploadingLogo">{{ __('landing_content.logo_upload') }}</span>
                                    <span x-show="uploadingLogo" x-cloak>{{ __('landing_content.saving') }}</span>
                                    <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" :disabled="uploadingLogo" @change="uploadLogo($event)">
                                </label>
                                <button
                                    type="button"
                                    :disabled="uploadingLogo"
                                    @click="removeLogo()"
                                    class="text-sm text-gray-500 dark:text-gray-400 hover:underline disabled:opacity-50"
                                >
                                    {{ __('landing_content.logo_reset') }}
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ __('landing_content.logo_help') }}</p>
                            <p x-show="logoError" x-text="logoError" x-cloak class="mt-1.5 text-sm text-red-600 dark:text-red-400"></p>
                        </div>
                    </div>
                </section>

                {{-- Hero --}}
                <section x-show="activeTab === 'hero'" x-cloak class="max-w-2xl">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_hero') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_hero_body') }}</p>

                    <div class="mt-4 space-y-4">
                        <x-landing-content.field label="{{ __('landing_content.field_hero_eyebrow') }}" model-en="form.hero_eyebrow_en" model-bn="form.hero_eyebrow_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_hero_tagline') }}" model-en="form.hero_tagline_en" model-bn="form.hero_tagline_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_hero_body') }}" model-en="form.hero_body_en" model-bn="form.hero_body_bn" textarea />
                        <x-landing-content.field label="{{ __('landing_content.field_hero_footnote') }}" model-en="form.hero_footnote_en" model-bn="form.hero_footnote_bn" />
                    </div>
                </section>

                {{-- Features --}}
                <section x-show="activeTab === 'features'" x-cloak class="max-w-2xl">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_features') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_features_body') }}</p>

                    <div class="mt-4 space-y-4">
                        <x-landing-content.field label="{{ __('landing_content.field_features_title') }}" model-en="form.features_title_en" model-bn="form.features_title_bn" />
                    </div>

                    @foreach ([1, 2, 3] as $n)
                        <div class="mt-5 pt-5 border-t border-border-subtle dark:border-gray-800">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('landing_content.feature_'.$n) }}</h3>
                            <div class="mt-3 space-y-4">
                                <x-landing-content.field label="{{ __('landing_content.field_feature_title') }}" model-en="form.feature_{{ $n }}_title_en" model-bn="form.feature_{{ $n }}_title_bn" />
                                <x-landing-content.field label="{{ __('landing_content.field_feature_body') }}" model-en="form.feature_{{ $n }}_body_en" model-bn="form.feature_{{ $n }}_body_bn" textarea />
                            </div>
                        </div>
                    @endforeach
                </section>

                {{-- Pricing --}}
                <section x-show="activeTab === 'pricing'" x-cloak class="max-w-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_pricing') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_pricing_body') }}</p>
                        </div>
                        <a
                            href="{{ route('pricing-plans.index') }}"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium px-3 py-1.5 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                        >
                            {{ __('landing_content.manage_pricing_plans') }}
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 6.75 17.25M17.25 6.75H8.25M17.25 6.75v9" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-4 space-y-4">
                        <x-landing-content.field label="{{ __('landing_content.field_pricing_title') }}" model-en="form.pricing_title_en" model-bn="form.pricing_title_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_pricing_body') }}" model-en="form.pricing_body_en" model-bn="form.pricing_body_bn" textarea />
                    </div>
                </section>

                {{-- About --}}
                <section x-show="activeTab === 'about'" x-cloak class="max-w-2xl">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_about') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_about_body') }}</p>

                    <div class="mt-4 space-y-4">
                        <x-landing-content.field label="{{ __('landing_content.field_about_title') }}" model-en="form.about_title_en" model-bn="form.about_title_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_about_body') }}" model-en="form.about_body_en" model-bn="form.about_body_bn" textarea />
                        <x-landing-content.field label="{{ __('landing_content.field_about_stat_response_types') }}" model-en="form.about_stat_response_types_value_en" model-bn="form.about_stat_response_types_value_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_about_stat_languages') }}" model-en="form.about_stat_languages_value_en" model-bn="form.about_stat_languages_value_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_about_stat_offline') }}" model-en="form.about_stat_offline_value_en" model-bn="form.about_stat_offline_value_bn" />
                    </div>
                </section>

                {{-- Contact --}}
                <section x-show="activeTab === 'contact'" x-cloak class="max-w-2xl">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('landing_content.section_contact') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('landing_content.section_contact_body') }}</p>

                    <div class="mt-4 space-y-4">
                        <x-landing-content.field label="{{ __('landing_content.field_contact_title') }}" model-en="form.contact_title_en" model-bn="form.contact_title_bn" />
                        <x-landing-content.field label="{{ __('landing_content.field_contact_body') }}" model-en="form.contact_body_en" model-bn="form.contact_body_bn" textarea />
                        <x-landing-content.field label="{{ __('landing_content.field_contact_office_address') }}" model-en="form.contact_office_address_en" model-bn="form.contact_office_address_bn" textarea />

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('landing_content.field_contact_email') }}</label>
                                <input
                                    type="email"
                                    x-model="form.contact_email"
                                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                                >
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('landing_content.field_contact_phone') }}</label>
                                <input
                                    type="text"
                                    dir="ltr"
                                    x-model="form.contact_phone"
                                    class="w-full rounded-control border border-border-subtle dark:border-gray-700 bg-surface dark:bg-gray-900 text-sm px-2 py-1.5"
                                >
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>

        <div x-show="!loading && !loadError" x-cloak class="flex shrink-0 items-center gap-3 border-t border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 px-4 py-3 sm:px-6">
            <button
                type="button"
                :disabled="saving"
                @click="save()"
                class="rounded-control bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-sm font-medium px-4 py-2 transition"
            >
                <span x-show="!saving">{{ __('landing_content.save') }}</span>
                <span x-show="saving" x-cloak>{{ __('landing_content.saving') }}</span>
            </button>

            <p x-show="saveError" x-text="saveError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>
            <p x-show="savedAt && !saveError" x-cloak class="text-sm text-green-600 dark:text-green-400">{{ __('landing_content.saved') }}</p>
        </div>
    </div>
</x-app-layout>
