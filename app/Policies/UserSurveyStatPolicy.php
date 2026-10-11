<?php

namespace App\Policies;

use App\Models\User;

/**
 * User productivity stats (spec §10.3) — reuses report.view/report.export
 * rather than a new permission, since "who collected how many" is a
 * reporting concern the same way a saved report or analytics dashboard is.
 * No model instance to check organization against: every query in
 * App\Http\Controllers\Api\V1\Exports\UserStatsController/
 * OrganizationUserStatsController is already scoped to the caller's own
 * organization at the query level.
 */
class UserSurveyStatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('report.view');
    }

    public function export(User $user): bool
    {
        return $user->can('report.export');
    }
}
