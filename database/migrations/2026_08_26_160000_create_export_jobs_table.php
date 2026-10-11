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
        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Null when this is an org-wide export (spec §10.2) rather than
            // a single survey's export.
            $table->foreignId('survey_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            // survey | organization | user_stats — which export endpoint
            // produced this job, since all three share one job/notification
            // shape (spec §10.2/§10.3) but build very different sheets.
            $table->string('type');

            $table->string('format', 10);
            $table->string('status', 20)->default('pending');

            // Scope/columns/filters the admin picked (spec §10.1): all |
            // filtered | selected | custom, plus the filters or response ids
            // and the optional custom column list. Read as a whole, never
            // queried into.
            $table->json('options')->nullable();

            // Set once the queued job finishes writing the file — never a
            // publicly-served path (spec §10.4); downloaded only via a
            // signed, expiring URL.
            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['requested_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
