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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('original_filename');
            $table->string('file_path');

            // uploaded (preview available) -> mapping_confirmed (queued) ->
            // processing -> completed | failed (spec §10.4's upload -> preview
            // -> column mapping -> validation -> duplicate detection -> summary
            // pipeline).
            $table->string('status', 20)->default('uploaded');

            // Header row read from the uploaded file, cached here so the
            // preview/mapping step never has to re-open it.
            $table->json('detected_columns')->nullable();

            // { "name": "Full Name", "email": "Email Address", ... } —
            // respondent field => source column, chosen by the admin during
            // the mapping step.
            $table->json('column_mapping')->nullable();

            // Per-row outcome counts plus per-row error detail once
            // processing finishes, so a bad import can be traced later
            // (spec §10.4's import history).
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('imported_count')->nullable();
            $table->unsignedInteger('duplicate_count')->nullable();
            $table->unsignedInteger('failed_count')->nullable();
            $table->json('row_errors')->nullable();

            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
