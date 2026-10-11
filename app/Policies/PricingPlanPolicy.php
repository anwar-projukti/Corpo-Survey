<?php

namespace App\Policies;

use App\Models\PricingPlan;
use App\Models\User;

/**
 * Pricing plan management is a Super Admin-only surface, the same
 * reasoning as OrganizationPolicy/LandingContentPolicy — Super Admin
 * already bypasses every check here via Gate::before (AuthServiceProvider),
 * so these methods only matter as the explicit deny for everyone else.
 */
class PricingPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, PricingPlan $pricingPlan): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, PricingPlan $pricingPlan): bool
    {
        return $user->isSuperAdmin();
    }
}
