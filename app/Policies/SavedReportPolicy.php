<?php

namespace App\Policies;

use App\Models\SavedReport;
use App\Models\Survey;
use App\Models\User;

/**
 * Super Admin bypasses every check here via Gate::before (AuthServiceProvider).
 * Every method re-checks organization_id explicitly rather than relying
 * solely on BelongsToOrganization's global scope, mirroring SurveyPolicy
 * and SurveyResponsePolicy.
 *
 * report.export (not a dedicated "manage" permission the spec never
 * defines) gates create/update/delete — saving a report configuration for
 * reuse is the same tier of access as exporting one, per spec §5.2's
 * existing report.view/report.export split (report.view only ever needs
 * to run/view a report someone else already saved).
 */
class SavedReportPolicy
{
    public function viewAny(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('report.view');
    }

    public function view(User $user, SavedReport $report): bool
    {
        return $this->sameOrganizationAsReport($user, $report) && $user->can('report.view');
    }

    public function create(User $user, Survey $survey): bool
    {
        return $this->sameOrganization($user, $survey) && $user->can('report.export');
    }

    public function update(User $user, SavedReport $report): bool
    {
        return $this->sameOrganizationAsReport($user, $report) && $user->can('report.export');
    }

    public function delete(User $user, SavedReport $report): bool
    {
        return $this->sameOrganizationAsReport($user, $report) && $user->can('report.export');
    }

    private function sameOrganization(User $user, Survey $survey): bool
    {
        return $user->organization_id === $survey->organization_id;
    }

    private function sameOrganizationAsReport(User $user, SavedReport $report): bool
    {
        return $user->organization_id === $report->organization_id;
    }
}
