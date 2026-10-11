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
        // Public landing page pricing tiers (e.g. Basic, Enterprise) —
        // unlike landing_contents (a single fixed row), this is a genuine
        // admin-managed list: add/remove/reorder plans freely.
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name_en');
            $table->string('name_bn');

            // Free text rather than a decimal column — spec calls for
            // labels like "$29/mo", "Custom", or "Contact us", not a
            // billable amount this app charges via any payment integration.
            $table->string('price_en');
            $table->string('price_bn');

            $table->text('description_en')->nullable();
            $table->text('description_bn')->nullable();

            // ["Up to 5 surveys", "500 responses/mo", ...] — ordered list,
            // same shape in both locales.
            $table->json('features_en')->nullable();
            $table->json('features_bn')->nullable();

            $table->string('button_text_en');
            $table->string('button_text_bn');
            // Where the plan's button links — an in-page anchor (#contact)
            // or a named route (register) resolved by the view, not a raw
            // absolute URL an admin could point off-site unintentionally.
            $table->string('button_target')->default('register');

            // Visually emphasized as the recommended tier (spec: "one
            // basic, one enterprise" — Enterprise is the one expected to
            // carry this). At most one plan should be highlighted at a
            // time; enforced in UpdatePricingPlanRequest, not the schema.
            $table->boolean('highlighted')->default(false);

            $table->unsignedInteger('order')->default(0);

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};
