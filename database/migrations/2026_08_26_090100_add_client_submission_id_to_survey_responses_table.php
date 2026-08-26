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
        Schema::table('survey_responses', function (Blueprint $table) {
            // A client-generated idempotency key (spec §7's offline
            // queueing) — lets a field user's device safely retry a
            // submission after a dropped connection without risking a
            // duplicate response, since the same key submitted twice is
            // rejected by the unique index rather than creating a second
            // row. Null for every submission that isn't replayed from the
            // offline queue (the public path never sets this at all).
            $table->string('client_submission_id', 64)->nullable()->after('collected_by_user_id');
            $table->unique(['collected_by_user_id', 'client_submission_id'], 'survey_responses_collector_client_submission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique('survey_responses_collector_client_submission_unique');
            $table->dropColumn('client_submission_id');
        });
    }
};
