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
        Schema::create('response_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_version_id')->constrained()->cascadeOnDelete();

            // Where the code was sent — validated as an email or phone at
            // the request layer, not here.
            $table->string('destination');

            // Hashed, never stored/logged in plaintext — same reasoning
            // as password storage.
            $table->string('code_hash');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['survey_version_id', 'destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_otps');
    }
};
