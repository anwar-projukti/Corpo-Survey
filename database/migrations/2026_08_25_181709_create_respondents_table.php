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
        Schema::create('respondents', function (Blueprint $table) {
            $table->id();

            // A respondent always belongs to the organization whose survey
            // they answered — never trusted from client input, stamped the
            // same way as every other tenant-scoped model.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Nullable: created only when we actually have identifying
            // info (an anonymous submission never creates a row here).
            // Encrypted at rest per spec §5.12 (PII).
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();

            $table->timestamps();

            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respondents');
    }
};
