<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a new teammate within the acting Org Admin's own organization
 * (spec §13 "Users & Permissions") — the flow App\Actions\Fortify\CreateNewUser
 * explicitly defers to rather than handling itself, since that action only
 * ever creates a brand-new organization plus its first Org Admin.
 */
class CreateTeammate
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string}  $attributes
     */
    public function handle(User $creator, array $attributes): User
    {
        $teammate = DB::transaction(function () use ($creator, $attributes) {
            $teammate = User::create([
                'organization_id' => $creator->organization_id,
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
            ]);

            // email_verified_at isn't mass-assignable (User::$fillable) —
            // force it here since this account is created directly by an
            // Org Admin with a real password, not through self-registration
            // with an email to verify. Without this, every /home (and any
            // other 'verified'-gated route) would redirect the brand-new
            // teammate straight to Fortify's "verify your email" screen on
            // their very first login.
            $teammate->forceFill(['email_verified_at' => now()])->save();

            $teammate->assignRole($attributes['role']);

            return $teammate;
        });

        $this->auditLogger->log('user.created', subject: $teammate, properties: [
            'name' => $teammate->name,
            'email' => $teammate->email,
            'role' => $attributes['role'],
        ], actor: $creator);

        return $teammate;
    }
}
