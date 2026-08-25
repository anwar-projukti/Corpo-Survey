<?php

namespace App\Policies;

use App\Models\SurveyVersion;
use App\Models\User;

/**
 * Governs whether a version's own content tree (sections, questions,
 * options, rules) can be viewed/modified — distinct from SurveyPolicy,
 * which governs the survey resource itself (publishing, deleting).
 * Super Admin bypasses via Gate::before as with every other policy.
 */
class SurveyVersionPolicy
{
    public function view(User $user, SurveyVersion $version): bool
    {
        return $user->organization_id === $this->surveyOrganizationId($version)
            && $user->can('survey.view');
    }

    /**
     * Whether $user may add/edit/delete/reorder content within $version.
     * Requires survey.edit AND that $version is still a draft — per spec
     * §6.2's versioning model, a published or archived version's content
     * is frozen; editing it means creating a new draft first.
     */
    public function edit(User $user, SurveyVersion $version): bool
    {
        return $user->organization_id === $this->surveyOrganizationId($version)
            && $user->can('survey.edit')
            && $version->isDraft();
    }

    /**
     * survey() is looked up without Survey's BelongsToOrganization scope:
     * for a user in a *different* org, the scoped relation would resolve
     * to null (the row exists, it's just invisible to that scope) and
     * crash a direct property/relation access instead of correctly
     * falling through to a "false" authorization result.
     */
    private function surveyOrganizationId(SurveyVersion $version): ?int
    {
        return $version->survey()->withoutGlobalScopes()->value('organization_id');
    }
}
