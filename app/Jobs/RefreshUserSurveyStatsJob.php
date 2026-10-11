<?php

namespace App\Jobs;

use App\Models\SurveyResponse;
use App\Models\UserSurveyStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds user_survey_stats (spec §10.3) from scratch every run, scheduled
 * nightly (routes/console.php). A materialized table rather than a live
 * aggregation, since the org-wide "who collected how many" report scans
 * every field user's every response and only needs to be as fresh as
 * "since last night" to be useful — MyStatsController's live per-request
 * aggregation is fine for one field user's own rows, but doesn't scale to
 * an org-wide ranking once response volume grows.
 *
 * Full rebuild (not an incremental upsert-since-last-run) deliberately —
 * response edits/deletes/review changes never touch collected_by_user_id
 * counts individually, so there's no single "what changed since last time"
 * signal to upsert from; a nightly full recompute is simple and cheap
 * enough at this table's scale (grouped aggregate, not per-response work).
 */
class RefreshUserSurveyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $rows = SurveyResponse::query()
            ->withoutGlobalScopes()
            ->whereNotNull('collected_by_user_id')
            ->selectRaw('organization_id, collected_by_user_id as user_id, survey_id, count(*) as response_count, max(submitted_at) as last_submitted_at')
            ->groupBy('organization_id', 'collected_by_user_id', 'survey_id')
            ->get();

        DB::transaction(function () use ($rows) {
            // delete(), not truncate(): TRUNCATE is DDL on MySQL and
            // implicitly commits whatever transaction it runs inside,
            // so the wrapping DB::transaction()'s own commit below then
            // fails with "There is no active transaction." SQLite (the
            // test suite's driver) has no such implicit-commit behavior,
            // which is why this went uncaught until run against MySQL.
            UserSurveyStat::query()->delete();

            foreach ($rows->chunk(500) as $chunk) {
                UserSurveyStat::query()->insert($chunk->map(fn ($row) => [
                    'organization_id' => $row->organization_id,
                    'user_id' => $row->user_id,
                    'survey_id' => $row->survey_id,
                    'response_count' => $row->response_count,
                    'last_submitted_at' => $row->last_submitted_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }
        });
    }
}
