<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Materialized per user/survey productivity totals (spec §10.3) —
        // refreshed by a nightly job (App\Jobs\RefreshUserSurveyStatsJob)
        // rather than aggregated live on every request, since an org-wide
        // "who collected how many" report scans every field user's every
        // response and only needs to be as fresh as "since last night" to
        // be useful.
        Schema::create('user_survey_stats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('response_count')->default(0);
            $table->timestamp('last_submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'survey_id']);
            $table->index(['organization_id', 'survey_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_survey_stats');
    }
};
