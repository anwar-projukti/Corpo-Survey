@props(['class' => ''])

@php
    // Spec §13. Each entry: label key, route name (or null for a
    // not-yet-built destination), children (each itself a label/route
    // pair), and an optional 'can' closure — the permission/role gate a
    // user must pass for the entry to render at all. Routes that don't
    // exist yet resolve to '#' rather than throwing, since most of this
    // nav targets screens later phases will build. A section with no
    // visible children after filtering is dropped entirely rather than
    // shown as a dead-end heading.
    $user = auth()->user();

    $isFieldUser = $user->isFieldUserOnly();

    $sections = [
        [
            'label' => $isFieldUser ? 'nav.my_surveys' : 'nav.dashboard',
            'route' => $isFieldUser ? 'my.surveys.index' : 'home',
            'children' => [],
        ],
        [
            // Templates and a standalone Question Library aren't built —
            // no route, no controller, no view — so they're left out
            // rather than linking to a page that doesn't exist yet, and
            // "All Surveys" isn't a separate child link since it would
            // point at the exact same place as this parent. Reinstate
            // both once that feature lands and there's more than one
            // survey-related destination to distinguish.
            //
            // Excluded for a Field-User-only account even though they
            // hold survey.view: this is the admin builder, which
            // SurveyPageController blocks for them with a 403 — they only
            // ever work a survey through /my/surveys once assigned (spec
            // §7), which is what Dashboard already points them to above.
            //
            // Also excluded for Super Admin: they manage organizations
            // (spec §13 "System Admin"), not survey content — even though
            // Gate::before still lets them view/edit any organization's
            // survey for support purposes if they land on one directly,
            // there's no cross-org survey list for them to browse here.
            'label' => 'nav.surveys',
            'route' => 'surveys.index',
            'can' => fn ($u) => ! $isFieldUser && ! $u->isSuperAdmin() && $u->can('survey.view'),
            'children' => [],
        ],
        [
            // The response table/detail views themselves are reached
            // per-survey (from that survey's own builder header) rather
            // than as a standalone cross-survey list — spec §8 never asked
            // for one. Export History is cross-survey, so it does get a
            // destination here (spec §10.2/§10.4) — as a direct link
            // rather than nested under a non-clickable "Responses" parent,
            // since there's only this one cross-survey destination to show.
            'label' => 'nav.export_history',
            'route' => 'export-history.index',
            'can' => fn ($u) => ! $u->isSuperAdmin() && $u->can('response.export'),
            'children' => [],
        ],
        [
            // Direct link rather than nested under a non-clickable
            // "Analytics" parent, since User Productivity is the only
            // cross-survey analytics destination — per-survey analytics
            // is reached from that survey's own builder header instead.
            'label' => 'nav.user_productivity',
            'route' => 'user-stats.index',
            'can' => fn ($u) => ! $u->isSuperAdmin() && $u->can('report.view'),
            'children' => [],
        ],
        [
            // Same reasoning — "Respondents" had exactly one child.
            'label' => 'nav.import_respondents',
            'route' => 'respondent-imports.index',
            'can' => fn ($u) => ! $u->isSuperAdmin() && $u->can('response.import'),
            'children' => [],
        ],
        [
            // An Org Admin adding/reviewing their own organization's
            // teammates (spec §13) — App\Policies\UserPolicy, gated by
            // user.manage same as this permission check.
            'label' => 'nav.users_and_permissions',
            'route' => 'users.index',
            'can' => fn ($u) => ! $u->isSuperAdmin() && $u->can('user.manage'),
            'children' => [],
        ],
        [
            // System Admin-only: every organization in the system, not
            // just the acting admin's own (spec §13 "System Admin") —
            // App\Policies\OrganizationPolicy, which only Super Admin
            // ever passes.
            'label' => 'nav.organizations',
            'route' => 'organizations.index',
            'can' => fn ($u) => $u->isSuperAdmin(),
            'children' => [],
        ],
        [
            // System Admin-only: edit the public landing page's content
            // (hero, features, pricing, about, contact) —
            // App\Policies\LandingContentPolicy, which only Super Admin
            // ever passes.
            'label' => 'nav.landing_content',
            'route' => 'landing-content.index',
            'can' => fn ($u) => $u->isSuperAdmin(),
            'children' => [],
        ],
        [
            // System Admin-only: manage the public landing page's pricing
            // tiers — App\Policies\PricingPlanPolicy, which only Super
            // Admin ever passes.
            'label' => 'nav.pricing_plans',
            'route' => 'pricing-plans.index',
            'can' => fn ($u) => $u->isSuperAdmin(),
            'children' => [],
        ],
        [
            // A walkthrough of the survey builder for whoever actually
            // uses it — same gate as nav.surveys above (excludes a
            // Field-User-only account, who never sees the builder, and
            // Super Admin, who manages organizations rather than survey
            // content).
            'label' => 'nav.help',
            'route' => 'help.survey-builder',
            'can' => fn ($u) => ! $isFieldUser && ! $u->isSuperAdmin() && $u->can('survey.view'),
            'children' => [],
        ],
    ];

    $sections = array_values(array_filter(
        $sections,
        fn ($section) => ! isset($section['can']) || $section['can']($user)
    ));

    $linkTarget = function (?string $routeName) {
        $exists = $routeName && \Illuminate\Support\Facades\Route::has($routeName);

        return [
            'href' => $exists ? route($routeName) : '#',
            'active' => $exists && request()->routeIs($routeName),
        ];
    };
@endphp

<aside aria-label="{{ __('messages.nav.primary_navigation') }}" class="{{ $class }} w-64 shrink-0 flex-col border-r border-border-subtle dark:border-gray-800 bg-surface dark:bg-gray-900">
    <div class="min-h-16 flex flex-col justify-center px-6 py-3 border-b border-border-subtle dark:border-gray-800 min-w-0">
        <div class="flex items-center gap-2 min-w-0">
            <x-app-logo class="h-7 w-7 shrink-0" />
            <span class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">{{ config('app.name') }}</span>
        </div>
        @if ($user->primaryRoleName())
            <div class="mt-0.5 flex items-center gap-1.5 min-w-0">
                <span class="text-xs text-gray-500 dark:text-gray-400 truncate shrink-0">
                    {{ __('messages.roles.'.$user->primaryRoleName()) }}
                </span>

                @if ($organization = $user->organization)
                    @if ($organization->pricingPlan)
                        <span class="text-xs text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span>
                        <span class="truncate text-xs font-medium text-brand-600 dark:text-brand-400" title="{{ $organization->pricingPlan->text('name', app()->getLocale()) }}">
                            {{ $organization->pricingPlan->text('name', app()->getLocale()) }}
                        </span>
                    @endif

                    @if ($organization->onTrial())
                        @php($daysLeft = (int) ceil(now()->floatDiffInDays($organization->trial_ends_at)))
                        <span class="shrink-0 inline-flex items-center rounded-full bg-green-50 dark:bg-green-950 px-1.5 py-0.5 text-[10px] font-semibold text-green-700 dark:text-green-300">
                            {{ trans_choice('messages.nav.trial_days_left', $daysLeft, ['count' => $daysLeft]) }}
                        </span>
                    @elseif ($organization->trialExpired())
                        <span class="shrink-0 inline-flex items-center rounded-full bg-red-50 dark:bg-red-950 px-1.5 py-0.5 text-[10px] font-semibold text-red-700 dark:text-red-300">
                            {{ __('messages.nav.trial_expired') }}
                        </span>
                    @endif
                @endif
            </div>
        @endif
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($sections as $section)
            @php($target = $linkTarget($section['route']))

            <a
                href="{{ $target['href'] }}"
                @if($target['active']) aria-current="page" @endif
                class="flex items-center rounded-control px-3 py-2 text-sm font-medium transition
                    {{ $target['active']
                        ? 'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-surface-subtle dark:hover:bg-gray-800' }}"
            >
                {{ __('messages.'.$section['label']) }}
            </a>

            @if (count($section['children']))
                <div class="ml-3 pl-3 border-l border-border-subtle dark:border-gray-800 space-y-0.5">
                    @foreach ($section['children'] as $child)
                        @php($childTarget = $linkTarget($child['route']))
                        <a
                            href="{{ $childTarget['href'] }}"
                            @if($childTarget['active']) aria-current="page" @endif
                            class="block rounded-control px-3 py-1.5 text-sm transition
                                {{ $childTarget['active']
                                    ? 'text-brand-600 dark:text-brand-400 font-medium'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-surface-subtle dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100' }}"
                        >
                            {{ __('messages.'.$child['label']) }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>
</aside>
