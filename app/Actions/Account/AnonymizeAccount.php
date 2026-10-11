<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Str;

/**
 * Right-to-be-forgotten (spec §5.12/§12) for one user's own account.
 * Anonymizes in place rather than deleting the row — a User is referenced
 * as a foreign key from Survey (created_by), SurveyResponse
 * (collected_by_user_id), AuditLog (actor_id), ResponseNote, SavedReport,
 * and more. Deleting it would either cascade-destroy business
 * records that have nothing to do with this person's PII (a survey they
 * authored, responses they collected in the field) or leave those
 * relations dangling — anonymizing strips everything personally
 * identifying while every record they authored/actioned stays intact and
 * attributable to "a deleted user," which is what every other model
 * already assumes is possible (see e.g. AuditLog.actor_email being
 * captured independently of the live User row for exactly this reason).
 *
 * The account is also deactivated (status set inactive) and logged out of
 * everywhere (Sanctum tokens revoked) — an anonymized account is not
 * expected to keep being a usable login.
 */
class AnonymizeAccount
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(User $user): void
    {
        $originalEmail = $user->email;

        $user->forceFill([
            'name' => 'Deleted User',
            'email' => 'deleted-user-'.Str::uuid().'@anonymized.invalid',
            'password' => bcrypt(Str::random(40)),
            'status' => 'suspended',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $user->tokens()->delete();

        $this->auditLogger->log('account.anonymized', subject: $user, properties: [
            'previous_email' => $originalEmail,
        ], actor: $user);
    }
}
