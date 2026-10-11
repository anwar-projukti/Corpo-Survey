<?php

namespace App\Providers;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments) {
            if (! $user->isSuperAdmin()) {
                return null;
            }

            // The one carve-out from the blanket Super Admin bypass:
            // creating a survey stamps organization_id from the creator
            // (App\Actions\Surveys\CreateSurvey), which Super Admin has
            // none of. Super Admin manages organizations/users, not
            // survey content directly — every other Survey ability (view,
            // edit, delete, publish, ...) still bypasses normally, since
            // those operate on a survey that already belongs to some
            // organization. Both ability spellings are excluded:
            // SurveyPolicy::create() is reached via ability "create" with
            // Survey::class as the argument, but that method's own body
            // then calls $user->can('survey.create') — a second,
            // independent Gate check by permission name that would
            // otherwise hit this same before-callback and bypass again.
            if ($ability === 'create' && ($arguments[0] ?? null) === Survey::class) {
                return null;
            }

            if ($ability === 'survey.create') {
                return null;
            }

            return true;
        });
    }
}
