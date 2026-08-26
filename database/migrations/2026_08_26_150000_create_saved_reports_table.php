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
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

            // Never null in practice (every report is saved by a logged-in
            // admin), but nullOnDelete rather than cascade — a saved report
            // stays usable by the rest of the org even if its creator's
            // account is later deleted, the same reasoning as
            // response_notes.user_id.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');

            // Everything a custom report needs to reproduce itself (spec
            // §9): which questions to chart, App\Support\ResponseFilterEngine
            // filters to apply, a chart type per question, and an optional
            // submitted_at date range — kept as one JSON blob rather than
            // normalized columns/tables, since it's read as a whole and
            // never queried into.
            $table->json('config');

            $table->timestamps();

            $table->index(['survey_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
