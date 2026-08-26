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
            // Independent of `status` (partial/complete, respondent-driven)
            // — this is the admin review decision (spec §8): approve,
            // reject, or archive a response. Every response starts pending.
            $table->string('review_status')->default('pending')->after('status');
            $table->index(['survey_id', 'review_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropIndex(['survey_id', 'review_status']);
            $table->dropColumn('review_status');
        });
    }
};
