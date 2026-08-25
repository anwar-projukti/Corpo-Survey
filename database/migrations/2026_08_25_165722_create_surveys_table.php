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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            // Denormalized pointers to survey_versions, maintained by the
            // publish/draft workflow — avoids a subquery to find "the
            // version currently being edited" or "the version currently
            // live" on every read. Nullable: a brand-new survey has a draft
            // but nothing published yet.
            $table->foreignId('current_draft_version_id')->nullable();
            $table->foreignId('current_published_version_id')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
