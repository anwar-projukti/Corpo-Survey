<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for the three login entry points (spec: separate
 * login URLs per audience). Each portal maps to the login route that serves
 * it and the role(s) allowed to authenticate through it — enforced by
 * App\Actions\Fortify\EnsureUserMatchesLoginPortal after Fortify's own
 * AttemptToAuthenticate runs, so a valid password on the wrong portal still
 * fails.
 */
class LoginPortal
{
    public const ADMIN = 'admin';

    public const ORG = 'org';

    public const USER = 'user';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::ADMIN, self::ORG, self::USER];
    }

    public static function isValid(string $portal): bool
    {
        return in_array($portal, self::all(), true);
    }

    /**
     * The role(s) a user must hold to authenticate through the given portal.
     * The USER portal has none — it's the catch-all for every role besides
     * Super Admin and Org Admin (Editor, Field User, Viewer, and any role
     * added later), so it's checked by exclusion in allows() rather than by
     * an explicit list here.
     *
     * @return list<string>
     */
    public static function rolesFor(string $portal): array
    {
        return match ($portal) {
            self::ADMIN => ['Super Admin'],
            self::ORG => ['Org Admin'],
            default => [],
        };
    }

    public static function allows(string $portal, User $user): bool
    {
        if ($portal === self::USER) {
            return ! $user->hasAnyRole([...self::rolesFor(self::ADMIN), ...self::rolesFor(self::ORG)]);
        }

        return $user->hasAnyRole(self::rolesFor($portal));
    }

    /**
     * The portal a given user is expected to sign in through, used to
     * point a rejected login at the right one.
     */
    public static function for(User $user): string
    {
        if (self::allows(self::ADMIN, $user)) {
            return self::ADMIN;
        }

        if (self::allows(self::ORG, $user)) {
            return self::ORG;
        }

        return self::USER;
    }

    public static function loginRouteFor(string $portal): string
    {
        return match ($portal) {
            self::ADMIN => 'admin.login',
            self::ORG => 'org.login',
            default => 'login',
        };
    }

    public static function titleKeyFor(string $portal): string
    {
        return match ($portal) {
            self::ADMIN => 'messages.auth.admin_login_title',
            self::ORG => 'messages.auth.org_login_title',
            default => 'messages.auth.login_title',
        };
    }
}
