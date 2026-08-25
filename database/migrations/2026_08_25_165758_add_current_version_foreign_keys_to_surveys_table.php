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
        Schema::table('surveys', function (Blueprint $table) {
            $table->foreign('current_draft_version_id')->references('id')->on('survey_versions')->nullOnDelete();
            $table->foreign('current_published_version_id')->references('id')->on('survey_versions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['current_draft_version_id']);
            $table->dropForeign(['current_published_version_id']);
        });
    }
};
