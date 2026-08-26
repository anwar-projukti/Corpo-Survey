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
        Schema::create('response_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();

            // Never null in practice (every note is left by a logged-in
            // admin), but nullOnDelete rather than cascade — a note stays
            // on the record as history even if its author's account is
            // later deleted, the same reasoning as audit_logs.actor_id.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('body');

            $table->timestamps();

            $table->index('survey_response_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_notes');
    }
};
