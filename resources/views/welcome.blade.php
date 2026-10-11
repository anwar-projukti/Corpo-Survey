@php
    // Every string below comes from the single LandingContent row, which
    // falls back field-by-field to the matching lang/*/messages.php
    // 'landing.*' string when a Super Admin hasn't customized it yet (see
    // LandingContent::text()) — so this page renders identically to before
    // the admin editor existed until someone actually edits a field.
    $landingContent = \App\Models\LandingContent::current();
    $locale = app()->getLocale();
    $lc = fn (string $field) => $landingContent->text($field, $locale);
    $pricingPlans = \App\Models\PricingPlan::query()->orderBy('order')->get();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials.head')
</head>
<body class="antialiased font-sans bg-surface-subtle dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <header
        x-data="{
            scrolled: false,
            mobileOpen: false,
            activeSection: '',
            sections: ['features', 'pricing', 'about', 'contact'],
            initScrollSpy() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.activeSection = entry.target.id;
                        }
                    });
                }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

                this.sections.forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) observer.observe(el);
                });
            },
        }"
        x-init="scrolled = window.scrollY > 8; window.addEventListener('scroll', () => scrolled = window.scrollY > 8); initScrollSpy()"
        :class="scrolled ? 'border-border-subtle dark:border-gray-800 bg-surface/80 dark:bg-gray-950/70 shadow-sm shadow-gray-900/5' : 'border-transparent bg-transparent'"
        class="sticky top-0 z-50 border-b backdrop-blur-md transition-colors duration-300"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2.5">
                <x-app-logo class="h-8 w-8" />
                <span class="text-[15px] font-bold tracking-tight text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
            </a>

            <nav class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-1 rounded-full border border-border-subtle/70 dark:border-gray-800 bg-surface/60 dark:bg-gray-900/50 px-1.5 py-1.5 backdrop-blur-sm lg:flex">
                <a
                    href="#features"
                    :aria-current="activeSection === 'features' ? 'true' : null"
                    :class="activeSection === 'features' ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/25' : 'text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                >{{ __('messages.landing.nav_features') }}</a>
                <a
                    href="#pricing"
                    :aria-current="activeSection === 'pricing' ? 'true' : null"
                    :class="activeSection === 'pricing' ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/25' : 'text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                >{{ __('messages.landing.nav_pricing') }}</a>
                <a
                    href="#about"
                    :aria-current="activeSection === 'about' ? 'true' : null"
                    :class="activeSection === 'about' ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/25' : 'text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                >{{ __('messages.landing.nav_about') }}</a>
                <a
                    href="#contact"
                    :aria-current="activeSection === 'contact' ? 'true' : null"
                    :class="activeSection === 'contact' ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/25' : 'text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                >{{ __('messages.landing.nav_contact') }}</a>
            </nav>

            <div class="flex items-center gap-2">
                <div class="hidden items-center gap-2 sm:flex">
                    <x-language-switcher />
                    <x-theme-switcher />
                </div>

                <div class="hidden items-center gap-2 lg:flex">
                    @auth
                        <a
                            href="{{ route('home') }}"
                            class="h-9 px-4 inline-flex items-center rounded-control bg-brand-600 text-white text-sm font-semibold shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 hover:shadow-md hover:shadow-brand-600/25"
                        >
                            {{ __('messages.landing.go_to_dashboard') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="h-9 px-3.5 inline-flex items-center rounded-control text-sm font-medium text-gray-600 dark:text-gray-300 transition hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100"
                        >
                            {{ __('messages.actions.log_in') }}
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="h-9 px-4 inline-flex items-center rounded-control bg-brand-600 text-white text-sm font-semibold shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 hover:shadow-md hover:shadow-brand-600/25"
                        >
                            {{ __('messages.landing.get_started') }}
                        </a>
                    @endauth
                </div>

                <button
                    type="button"
                    @click="mobileOpen = !mobileOpen"
                    :aria-expanded="mobileOpen"
                    aria-label="{{ __('messages.nav.primary_navigation') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-control text-gray-600 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800 lg:hidden"
                >
                    <svg x-show="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div
            x-show="mobileOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            @click.outside="mobileOpen = false"
            class="border-t border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-950 px-4 py-4 sm:px-6 lg:hidden"
        >
            <nav class="flex flex-col gap-1">
                <a
                    @click="mobileOpen = false"
                    href="#features"
                    :aria-current="activeSection === 'features' ? 'true' : null"
                    :class="activeSection === 'features' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800'"
                    class="rounded-control px-3 py-2.5 text-sm font-medium"
                >{{ __('messages.landing.nav_features') }}</a>
                <a
                    @click="mobileOpen = false"
                    href="#pricing"
                    :aria-current="activeSection === 'pricing' ? 'true' : null"
                    :class="activeSection === 'pricing' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800'"
                    class="rounded-control px-3 py-2.5 text-sm font-medium"
                >{{ __('messages.landing.nav_pricing') }}</a>
                <a
                    @click="mobileOpen = false"
                    href="#about"
                    :aria-current="activeSection === 'about' ? 'true' : null"
                    :class="activeSection === 'about' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800'"
                    class="rounded-control px-3 py-2.5 text-sm font-medium"
                >{{ __('messages.landing.nav_about') }}</a>
                <a
                    @click="mobileOpen = false"
                    href="#contact"
                    :aria-current="activeSection === 'contact' ? 'true' : null"
                    :class="activeSection === 'contact' ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800'"
                    class="rounded-control px-3 py-2.5 text-sm font-medium"
                >{{ __('messages.landing.nav_contact') }}</a>
            </nav>

            <div class="mt-4 flex items-center gap-2 sm:hidden">
                <x-language-switcher />
                <x-theme-switcher />
            </div>

            <div class="mt-4 flex flex-col gap-2 border-t border-border-subtle dark:border-gray-800 pt-4">
                @auth
                    <a
                        href="{{ route('home') }}"
                        class="h-10 inline-flex items-center justify-center rounded-control bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition"
                    >
                        {{ __('messages.landing.go_to_dashboard') }}
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="h-10 inline-flex items-center justify-center rounded-control border border-border-subtle dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800 transition"
                    >
                        {{ __('messages.actions.log_in') }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="h-10 inline-flex items-center justify-center rounded-control bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 transition"
                    >
                        {{ __('messages.landing.get_started') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden px-4 pt-16 pb-20 sm:px-6 sm:pt-24 sm:pb-28">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem] bg-[radial-gradient(ellipse_60%_50%_at_50%_-10%,var(--tw-gradient-stops))] from-brand-100 via-transparent to-transparent dark:from-brand-950/40"
            ></div>

            <div class="mx-auto max-w-3xl text-center">
                <div class="inline-flex items-center gap-2 rounded-full border border-border-subtle dark:border-gray-800 bg-surface/80 dark:bg-gray-900/60 px-3.5 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 backdrop-blur-sm">
                    <span class="flex h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    {{ $lc('hero_eyebrow') }}
                </div>

                <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-balance text-gray-900 dark:text-gray-100 sm:text-5xl lg:text-6xl">
                    {{ $lc('hero_tagline') }}
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    {{ $lc('hero_body') }}
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a
                            href="{{ route('home') }}"
                            class="h-12 w-full px-7 inline-flex items-center justify-center rounded-control bg-brand-600 text-white font-semibold shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-600/30 sm:w-auto"
                        >
                            {{ __('messages.landing.go_to_dashboard') }}
                        </a>
                    @else
                        <a
                            href="{{ route('register') }}"
                            class="h-12 w-full px-7 inline-flex items-center justify-center rounded-control bg-brand-600 text-white font-semibold shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 hover:shadow-xl hover:shadow-brand-600/30 sm:w-auto"
                        >
                            {{ __('messages.landing.get_started') }}
                        </a>
                        <a
                            href="{{ route('login') }}"
                            class="h-12 w-full px-7 inline-flex items-center justify-center rounded-control border border-border-subtle dark:border-gray-700 bg-surface/80 dark:bg-gray-900/60 text-gray-700 dark:text-gray-200 font-semibold backdrop-blur-sm transition hover:bg-surface-subtle dark:hover:bg-gray-800 sm:w-auto"
                        >
                            {{ __('messages.actions.log_in') }}
                        </a>
                    @endauth
                </div>

                <p class="mt-6 text-sm text-gray-500 dark:text-gray-500">{{ $lc('hero_footnote') }}</p>
            </div>
        </section>

        <section id="features" class="px-4 sm:px-6 pb-20 scroll-mt-16 sm:pb-28">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-xl text-center">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ __('messages.landing.nav_features') }}</h2>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-balance text-gray-900 dark:text-gray-100 sm:text-3xl">{{ $lc('features_title') }}</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-3">
                    <div class="group rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 transition hover:-translate-y-0.5 hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-lg hover:shadow-gray-900/5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3-15H6a1.5 1.5 0 0 0-1.5 1.5v16.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06l-3.622-3.622a1.5 1.5 0 0 0-1.06-.44Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">{{ $lc('feature_1_title') }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $lc('feature_1_body') }}</p>
                    </div>
                    <div class="group rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 transition hover:-translate-y-0.5 hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-lg hover:shadow-gray-900/5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">{{ $lc('feature_2_title') }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $lc('feature_2_body') }}</p>
                    </div>
                    <div class="group rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 transition hover:-translate-y-0.5 hover:border-brand-200 dark:hover:border-brand-800 hover:shadow-lg hover:shadow-gray-900/5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125 8.25 7.5l4.5 4.125L21 4.5M21 4.5h-4.5M21 4.5V9m0 6v5.25a1.5 1.5 0 0 1-1.5 1.5H4.5a1.5 1.5 0 0 1-1.5-1.5V15" />
                            </svg>
                        </div>
                        <h3 class="mt-4 font-semibold text-gray-900 dark:text-gray-100">{{ $lc('feature_3_title') }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $lc('feature_3_body') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="px-4 sm:px-6 pb-20 scroll-mt-16 sm:pb-28">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-xl text-center">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ __('messages.landing.nav_pricing') }}</h2>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-balance text-gray-900 dark:text-gray-100 sm:text-3xl">{{ $lc('pricing_title') }}</p>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">{{ $lc('pricing_body') }}</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 sm:items-start">
                    @foreach ($pricingPlans as $plan)
                        <div
                            class="relative rounded-card border p-8 {{ $plan->highlighted
                                ? 'border-brand-300 dark:border-brand-700 bg-surface dark:bg-gray-900 shadow-xl shadow-brand-600/10 sm:-translate-y-2'
                                : 'border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900' }}"
                        >
                            @if ($plan->highlighted)
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                                    {{ __('messages.landing.pricing_most_popular') }}
                                </span>
                            @endif

                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $plan->text('name', $locale) }}</h3>
                            <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100">{{ $plan->text('price', $locale) }}</p>
                            @if ($plan->hasTrial())
                                <p class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ trans_choice('messages.landing.pricing_trial_days', $plan->trial_days, ['count' => $plan->trial_days]) }}
                                </p>
                            @endif
                            @if ($plan->text('description', $locale))
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $plan->text('description', $locale) }}</p>
                            @endif

                            @if (count($plan->features($locale)))
                                <ul class="mt-6 space-y-3">
                                    @foreach ($plan->features($locale) as $feature)
                                        <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <a
                                href="{{ $plan->button_target === 'contact' ? '#contact' : route('register', ['plan' => $plan->id]) }}"
                                class="mt-8 flex h-11 items-center justify-center rounded-control font-semibold transition {{ $plan->highlighted
                                    ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 hover:shadow-md'
                                    : 'border border-border-subtle dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-surface-subtle dark:hover:bg-gray-800' }}"
                            >
                                {{ $plan->text('button_text', $locale) }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="about" class="px-4 sm:px-6 pb-20 scroll-mt-16 sm:pb-28">
            <div class="relative mx-auto max-w-5xl overflow-hidden rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-8 sm:p-12">
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full bg-brand-100 dark:bg-brand-950/40 blur-3xl"
                ></div>

                <div class="relative grid gap-10 sm:grid-cols-[1fr_1.4fr] sm:gap-12">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ __('messages.landing.nav_about') }}</h2>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-balance text-gray-900 dark:text-gray-100 sm:text-3xl">{{ $lc('about_title') }}</p>
                    </div>

                    <div>
                        <p class="text-gray-600 dark:text-gray-400">{{ $lc('about_body') }}</p>

                        <div class="mt-8 grid grid-cols-1 gap-4 border-t border-border-subtle dark:border-gray-800 pt-6 sm:grid-cols-3">
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 0 1 1.06 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.landing.about_stat_response_types_label') }}</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $lc('about_stat_response_types_value') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3.75h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m6.334-12.138a48.44 48.44 0 0 1 3.774 1.09M18 5.25c-1.058.093-2.11.223-3.152.393" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.landing.about_stat_languages_label') }}</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $lc('about_stat_languages_value') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.landing.about_stat_offline_label') }}</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $lc('about_stat_offline_value') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="px-4 sm:px-6 pb-20 scroll-mt-16 sm:pb-28">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-xl text-center">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ __('messages.landing.nav_contact') }}</h2>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">{{ $lc('contact_title') }}</p>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">{{ $lc('contact_body') }}</p>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-3">
                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0a2.25 2.25 0 0 0-2.25-2.25h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.landing.contact_email_label') }}</h3>
                        <a
                            href="mailto:{{ $landingContent->contact_email ?: config('app.contact_email') }}"
                            class="mt-1.5 inline-block text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline"
                        >
                            {{ $landingContent->contact_email ?: config('app.contact_email') }}
                        </a>
                    </div>

                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293a1.125 1.125 0 0 1-1.21.38 12.035 12.035 0 0 1-7.143-7.143 1.125 1.125 0 0 1 .38-1.21l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.landing.contact_phone_label') }}</h3>
                        <a
                            href="tel:{{ $landingContent->contact_phone ?: config('app.contact_phone') }}"
                            class="mt-1.5 inline-block text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline"
                            dir="ltr"
                        >
                            {{ $landingContent->contact_phone ?: config('app.contact_phone') }}
                        </a>
                    </div>

                    <div class="rounded-card border border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900 p-6 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-control bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('messages.landing.contact_office_label') }}</h3>
                        <p class="mt-1.5 whitespace-pre-line text-sm font-medium text-gray-700 dark:text-gray-300">{{ $lc('contact_office_address') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-border-subtle dark:border-gray-800 px-4 sm:px-6 py-8">
        <div class="max-w-5xl mx-auto flex flex-wrap items-center gap-x-6 gap-y-4">
            <div class="flex items-center gap-2">
                <x-app-logo class="h-7 w-7" />
                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
            </div>

            <nav class="flex items-center gap-6">
                <a href="#features" class="text-sm text-gray-600 dark:text-gray-300 hover:text-brand-600 dark:hover:text-brand-400 transition">{{ __('messages.landing.nav_features') }}</a>
                <a href="#pricing" class="text-sm text-gray-600 dark:text-gray-300 hover:text-brand-600 dark:hover:text-brand-400 transition">{{ __('messages.landing.nav_pricing') }}</a>
                <a href="#about" class="text-sm text-gray-600 dark:text-gray-300 hover:text-brand-600 dark:hover:text-brand-400 transition">{{ __('messages.landing.nav_about') }}</a>
                <a href="#contact" class="text-sm text-gray-600 dark:text-gray-300 hover:text-brand-600 dark:hover:text-brand-400 transition">{{ __('messages.landing.nav_contact') }}</a>
            </nav>

            <span class="ml-auto text-sm text-gray-500 dark:text-gray-400">&copy; {{ now()->year }} {{ config('app.name') }}</span>
        </div>
    </footer>
</body>
</html>
