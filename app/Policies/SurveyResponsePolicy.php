<?php

namespace App\Policies;

use App\Models\SurveyResponse;
use App\Models\User;

/**
 * Super Admin bypasses every check here via Gate::before (AuthServiceProvider).
 * Every method re-checks organization_id explicitly rather than relying
 * solely on BelongsToOrganization's global scope, mirroring SurveyPolicy —
 * the scope stops a query from *returning* another tenant's response, but
 * the policy is the last line of defense if a response instance ever
 * reaches here some other way.
 */
class SurveyResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('response.view');
    }

    public function view(User $user, SurveyResponse $response): bool
    {
        return $this->sameOrganization($user, $response) && $user->can('response.view');
    }

    public function update(User $user, SurveyResponse $response): bool
    {
        return $this->sameOrganization($user, $response) && $user->can('response.edit');
    }

    public function delete(User $user, SurveyResponse $response): bool
    {
        return $this->sameOrganization($user, $response) && $user->can('response.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('response.export');
    }

    private function sameOrganization(User $user, SurveyResponse $response): bool
    {
        return $user->organization_id === $response->organization_id;
    }
}
