<?php

use App\Jobs\PurgeExpiredDataJob;
use App\Jobs\RefreshUserSurveyStatsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// User productivity stats (spec §10.3) — a materialized summary table
// refreshed nightly rather than aggregated live on every request.
Schedule::job(new RefreshUserSurveyStatsJob)->dailyAt('01:00');

// Retention/purge (spec §5.12) — hard-deletes rows past their soft-delete
// retention window (config('retention.soft_delete_days')). Run after the
// stats refresh above so a response purged tonight has already been
// counted in tonight's stats rebuild rather than disappearing mid-count.
Schedule::job(new PurgeExpiredDataJob)->dailyAt('02:00');
