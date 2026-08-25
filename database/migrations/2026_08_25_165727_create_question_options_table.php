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
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');

            // Stored/exported value, may differ from the display label
            // (e.g. label "Very Satisfied", value "5"). Defaults to the
            // label when not explicitly set by the builder.
            $table->string('value');

            // For Image Choice questions (spec §6.1); null for plain options.
            $table->string('image_path')->nullable();

            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['survey_question_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
