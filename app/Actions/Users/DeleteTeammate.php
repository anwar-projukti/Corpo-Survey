<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;

/**
 * Deactivates a teammate within the acting Org Admin's own organization.
 * A soft delete (User uses SoftDeletes), not a hard one — their historical
 * activity (survey responses collected, audit log entries, edits made)
 * stays attributed to them; only their ability to sign in disappears,
 * since Laravel's default Eloquent auth provider excludes soft-deleted
 * rows from the query login runs against. UserPolicy::delete() already
 * refuses this for another Org Admin before this action ever runs.
 */
class DeleteTeammate
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(User $actor, User $teammate): void
    {
        $this->auditLogger->log('user.deleted', subject: $teammate, properties: [
            'name' => $teammate->name,
            'email' => $teammate->email,
        ], actor: $actor);

        $teammate->delete();
    }
}
