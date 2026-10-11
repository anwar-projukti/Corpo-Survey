<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Hard-deletes rows that have been soft-deleted for longer than
 * config('retention.soft_delete_days') (spec §5.12) — without this, a
 * soft delete is a de facto "forever" retention with nothing ever
 * actually purged. Scheduled nightly (routes/console.php).
 *
 * Each model is purged independently by its own deleted_at, not by
 * cascading down from Organization — a Survey or SurveyResponse can be
 * soft-deleted long before its parent Organization ever is, and each
 * needs its own retention clock. Cascade-on-delete foreign keys still
 * clean up any genuinely orphaned children once a parent is purged.
 */
class PurgeExpiredDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AuditLogger $auditLogger): void
    {
        $cutoff = now()->subDays((int) config('retention.soft_delete_days'));

        $purged = [
            'survey_responses' => SurveyResponse::onlyTrashed()->where('deleted_at', '<', $cutoff)->count(),
            'surveys' => Survey::onlyTrashed()->where('deleted_at', '<', $cutoff)->count(),
            'users' => User::onlyTrashed()->where('deleted_at', '<', $cutoff)->count(),
            'organizations' => Organization::onlyTrashed()->where('deleted_at', '<', $cutoff)->count(),
        ];

        // Children purged before parents — a response whose survey is
        // also past its own retention window should still be counted and
        // removed on its own terms rather than silently disappearing as a
        // side effect of the survey's cascade.
        SurveyResponse::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();
        Survey::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();
        User::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();
        Organization::onlyTrashed()->where('deleted_at', '<', $cutoff)->forceDelete();

        $auditLogger->log('data.retention_purge', properties: [
            'cutoff' => $cutoff->toIso8601String(),
            'purged' => $purged,
        ]);
    }
}
