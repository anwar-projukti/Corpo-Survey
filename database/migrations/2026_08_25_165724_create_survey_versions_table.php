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
        Schema::create('survey_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status')->default('draft'); // draft | published | archived

            // Per-version settings that make sense to change without
            // touching the questions themselves — public access mode
            // (spec §5.8), one-question-per-screen vs multi-question, etc.
            // Validated server-side via a DTO, same as question config.
            $table->json('settings')->nullable();

            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['survey_id', 'version_number']);
            $table->index(['survey_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_versions');
    }
};
