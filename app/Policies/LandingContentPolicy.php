<?php

namespace App\Policies;

use App\Models\LandingContent;
use App\Models\User;

/**
 * Public landing page content management is a Super Admin-only surface,
 * the same reasoning as OrganizationPolicy — Super Admin already bypasses
 * every check here via Gate::before (AuthServiceProvider), so these
 * methods only matter as the explicit deny for everyone else.
 */
class LandingContentPolicy
{
    public function view(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, LandingContent $landingContent): bool
    {
        return $user->isSuperAdmin();
    }
}
