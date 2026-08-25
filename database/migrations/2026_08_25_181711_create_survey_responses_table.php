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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

            // Responses stay tied to the exact version they were collected
            // under (spec §6.2) — restrictOnDelete rather than cascade:
            // a version with real responses attached should never be
            // deletable by an ordinary application code path.
            $table->foreignId('survey_version_id')->constrained()->restrictOnDelete();

            // Nullable: an anonymous public submission never creates or
            // references a respondent row.
            $table->foreignId('respondent_id')->nullable()->constrained()->nullOnDelete();

            // Set server-side only, never trusted from client input (spec
            // §7): who collected this on behalf of the respondent, if
            // anyone. Null = public self-serve.
            $table->foreignId('collected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('collection_method')->default('self_serve'); // self_serve | field_agent

            $table->string('status')->default('partial'); // partial | complete

            // Fingerprint of the signed respondent cookie (see
            // PublicSurveyAccess) — used to enforce duplicate_policy
            // without ever storing the raw cookie value. Null for
            // field-agent-collected responses, which don't go through
            // the cookie mechanism at all.
            $table->string('respondent_fingerprint', 64)->nullable();

            $table->json('meta')->nullable(); // arbitrary submission context (browser, referrer, ...)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['survey_version_id', 'respondent_fingerprint']);
            $table->index(['organization_id', 'submitted_at']);
            $table->index('collected_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
