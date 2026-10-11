<?php

namespace App\Support;

/**
 * The roles an Org Admin may assign to a new teammate (spec §13 "Users &
 * Permissions") — deliberately excludes Org Admin and Super Admin so this
 * screen can never be used to create a peer admin or escalate to the
 * platform-wide role. Org Admin accounts are created only by registration
 * (App\Actions\Fortify\CreateNewUser) and Super Admin only by seeding/
 * direct database access.
 */
class AssignableRole
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return ['Editor', 'Field User', 'Viewer'];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
