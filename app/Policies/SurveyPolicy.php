<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

/**
 * Super Admin bypasses every check here via Gate::before (AuthServiceProvider)
 * and never reaches this class. Every method that receives a Survey
 * instance re-checks organization_id explicitly (via sameOrganization())
 * rather than relying solely on Survey's BelongsToOrganization global
 * scope — the scope stops a query from *returning* another tenant's
 * survey, but a policy is the last line of defense if a survey instance
 * ever reaches here some other way (e.g. a route-model-bound ID looked up
 * with scopes bypassed). viewAny() has no instance to check against, and
 * forceDelete() is unconditionally false — neither calls sameOrganization().
 */
class SurveyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('survey.view');
    }

    public function view(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('survey.view');
    }

    public function create(User $user): bool
    {
        return $user->can('survey.create');
    }

    public function update(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('survey.edit');
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('survey.delete');
    }

    public function publish(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('survey.publish');
    }

    public function restore(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('survey.delete');
    }

    public function forceDelete(User $user, Survey $survey): bool
    {
        return false;
    }

    private function sameOrganization(User $user, Survey $survey): bool
    {
        return $user->organization_id === $survey->organization_id;
    }
}
