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
        // Single-row table (spec: public landing page content, editable by
        // Super Admin from /landing-content) — a fixed set of named bilingual
        // columns rather than a generic key-value store, matching this
        // app's existing preference for explicit typed columns over JSON
        // blobs. LandingContent::current() enforces the single-row
        // invariant; a NULL column here means "not yet customized" and the
        // public page falls back to the matching lang/*/messages.php string,
        // so nothing regresses until a Super Admin actually edits a field.
        Schema::create('landing_contents', function (Blueprint $table) {
            $table->id();

            $table->string('hero_eyebrow_en')->nullable();
            $table->string('hero_eyebrow_bn')->nullable();
            $table->string('hero_tagline_en')->nullable();
            $table->string('hero_tagline_bn')->nullable();
            $table->text('hero_body_en')->nullable();
            $table->text('hero_body_bn')->nullable();
            $table->string('hero_footnote_en')->nullable();
            $table->string('hero_footnote_bn')->nullable();

            $table->string('features_title_en')->nullable();
            $table->string('features_title_bn')->nullable();

            $table->string('feature_1_title_en')->nullable();
            $table->string('feature_1_title_bn')->nullable();
            $table->text('feature_1_body_en')->nullable();
            $table->text('feature_1_body_bn')->nullable();

            $table->string('feature_2_title_en')->nullable();
            $table->string('feature_2_title_bn')->nullable();
            $table->text('feature_2_body_en')->nullable();
            $table->text('feature_2_body_bn')->nullable();

            $table->string('feature_3_title_en')->nullable();
            $table->string('feature_3_title_bn')->nullable();
            $table->text('feature_3_body_en')->nullable();
            $table->text('feature_3_body_bn')->nullable();

            $table->string('pricing_title_en')->nullable();
            $table->string('pricing_title_bn')->nullable();
            $table->text('pricing_body_en')->nullable();
            $table->text('pricing_body_bn')->nullable();
            $table->string('pricing_cta_en')->nullable();
            $table->string('pricing_cta_bn')->nullable();

            $table->string('about_title_en')->nullable();
            $table->string('about_title_bn')->nullable();
            $table->text('about_body_en')->nullable();
            $table->text('about_body_bn')->nullable();
            $table->string('about_stat_response_types_value_en')->nullable();
            $table->string('about_stat_response_types_value_bn')->nullable();
            $table->string('about_stat_languages_value_en')->nullable();
            $table->string('about_stat_languages_value_bn')->nullable();
            $table->string('about_stat_offline_value_en')->nullable();
            $table->string('about_stat_offline_value_bn')->nullable();

            $table->string('contact_title_en')->nullable();
            $table->string('contact_title_bn')->nullable();
            $table->text('contact_body_en')->nullable();
            $table->text('contact_body_bn')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_office_address_en')->nullable();
            $table->string('contact_office_address_bn')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_contents');
    }
};
