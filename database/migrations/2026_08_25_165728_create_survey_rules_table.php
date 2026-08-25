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
        Schema::create('survey_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_version_id')->constrained()->cascadeOnDelete();

            $table->string('name')->nullable();

            // Nested AND/OR condition tree referencing question IDs within
            // the same version, e.g. {"operator":"and","conditions":[...]}.
            // Validated via a SurveyRuleData DTO — never trusted as-is.
            $table->json('conditions');

            // show | hide | skip | go_to_section | end_survey | require | make_optional (spec §6.3)
            $table->string('action');

            // What the action applies to — a question_id or section_id,
            // shape depends on `action`. Kept as JSON rather than two
            // nullable FK columns since only one is ever relevant per rule.
            $table->json('target')->nullable();

            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['survey_version_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_rules');
    }
};
