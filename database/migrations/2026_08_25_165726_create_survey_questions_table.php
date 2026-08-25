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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_section_id')->constrained()->cascadeOnDelete();

            // e.g. short_text, single_choice, matrix, signature, ... (spec §6.1)
            $table->string('type');

            $table->string('label');
            $table->text('help_text')->nullable();

            // Type-specific settings (min/max, rating scale, matrix rows,
            // file size limits, ...). Shape varies per `type`; validated
            // server-side by a QuestionConfigData DTO keyed on type, never
            // trusted as-is. See app/Data/QuestionConfig.
            $table->json('config')->nullable();

            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['survey_section_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
