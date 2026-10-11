<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

/**
 * Organization management is a Super Admin-only surface — an Org Admin
 * manages their own organization's teammates via UserPolicy, never other
 * organizations. Super Admin already bypasses every check here via
 * Gate::before (AuthServiceProvider), so these methods only matter as the
 * explicit deny for everyone else.
 */
class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->isSuperAdmin();
    }
}
