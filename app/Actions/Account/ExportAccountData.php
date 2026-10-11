<?php

namespace App\Actions\Account;

use App\Models\User;

/**
 * Self-service "download my data" (spec §5.12/§12) — everything this user
 * account is personally identifiable in, in one JSON structure. Scoped to
 * exactly what this user themselves authored/actioned, never another
 * user's data (no admin bypass here — this is the individual's own GDPR
 * request, not an admin export tool, which is a distinct feature/spec §10).
 */
class ExportAccountData
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        return [
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'organization_id' => $user->organization_id,
                'roles' => $user->getRoleNames(),
                'locale' => $user->locale,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
            'surveys_created' => $user->surveys()->pluck('title', 'id'),
            'responses_collected' => $user->collectedResponses()
                ->select('id', 'survey_id', 'status', 'submitted_at')
                ->get(),
            'survey_assignments' => $user->assignedSurveys()->pluck('title', 'surveys.id'),
            'audit_log' => $user->auditLogEntries()
                ->latest('created_at')
                ->limit(500)
                ->get(['action', 'subject_type', 'subject_id', 'properties', 'ip_address', 'created_at']),
        ];
    }
}
