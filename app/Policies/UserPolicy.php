<?php

namespace App\Policies;

use App\Models\User;

/**
 * Managing an organization's own teammates (spec §13 "Users & Permissions")
 * — gated by user.manage, held only by Org Admin per
 * database/seeders/RolesAndPermissionsSeeder. Super Admin bypasses every
 * check here via Gate::before (AuthServiceProvider) and never reaches this
 * class.
 *
 * Which role may be *assigned* to a new teammate (never Org Admin or Super
 * Admin — see App\Support\AssignableRole) is a request-validation concern
 * in StoreTeammateRequest, not a policy boolean.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function view(User $user, User $teammate): bool
    {
        return $this->sameOrganization($user, $teammate) && $user->can('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    /**
     * An Org Admin may edit an ordinary teammate (Editor/Field User/
     * Viewer) but never another Org Admin — not even themselves through
     * this screen, which would let them demote their own role or lock
     * themselves out with no other admin left to undo it. Changing an
     * Org Admin's own name/role belongs to profile settings, not this
     * teammate-management surface.
     */
    public function update(User $user, User $teammate): bool
    {
        return $this->sameOrganization($user, $teammate)
            && $user->can('user.manage')
            && ! $teammate->hasRole('Org Admin');
    }

    /**
     * Same reasoning as update() — deleting (deactivating) an Org Admin,
     * including yourself, isn't available from this screen.
     */
    public function delete(User $user, User $teammate): bool
    {
        return $this->sameOrganization($user, $teammate)
            && $user->can('user.manage')
            && ! $teammate->hasRole('Org Admin');
    }

    private function sameOrganization(User $user, User $teammate): bool
    {
        return $user->organization_id === $teammate->organization_id;
    }
}
