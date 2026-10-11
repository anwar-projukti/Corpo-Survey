<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Updates an existing teammate within the acting Org Admin's own
 * organization — name, assigned role, and optionally a new password.
 * UserPolicy::update() already refuses this for another Org Admin before
 * this action ever runs.
 */
class UpdateTeammate
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{name?: string, role?: string, password?: string}  $attributes
     */
    public function handle(User $actor, User $teammate, array $attributes): User
    {
        DB::transaction(function () use ($teammate, $attributes) {
            $teammate->fill([
                ...(isset($attributes['name']) ? ['name' => $attributes['name']] : []),
                ...(isset($attributes['password']) ? ['password' => Hash::make($attributes['password'])] : []),
            ])->save();

            if (isset($attributes['role']) && ! $teammate->hasRole($attributes['role'])) {
                $teammate->syncRoles([$attributes['role']]);
            }
        });

        $this->auditLogger->log('user.updated', subject: $teammate, properties: [
            'name' => $teammate->name,
            'role' => $attributes['role'] ?? $teammate->primaryRoleName(),
            'password_changed' => isset($attributes['password']),
        ], actor: $actor);

        return $teammate->fresh();
    }
}
