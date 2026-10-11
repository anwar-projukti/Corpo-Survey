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
        // A survey-level "closed" state (spec §11's survey.closed webhook
        // event) — distinct from survey_versions.status (draft/published/
        // archived), which tracks one version's own lifecycle. Closing a
        // survey stops new public/field submissions against its published
        // version without touching that version's status at all, so a
        // closed survey's data and version history stay exactly as they
        // were the moment it closed.
        Schema::table('surveys', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
