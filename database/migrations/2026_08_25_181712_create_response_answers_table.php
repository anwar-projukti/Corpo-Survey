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
        Schema::create('response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->restrictOnDelete();

            // Shape varies by question type (spec §6.2's dynamic
            // architecture — no per-type columns). A single choice/select
            // question stores its question_options.value here; matrix
            // stores {row: column, ...}; file uploads store a stored path,
            // never trusted as a client-supplied path (spec §5.9).
            $table->json('value')->nullable();

            $table->timestamps();

            $table->unique(['survey_response_id', 'survey_question_id']);
            $table->index('survey_question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_answers');
    }
};
