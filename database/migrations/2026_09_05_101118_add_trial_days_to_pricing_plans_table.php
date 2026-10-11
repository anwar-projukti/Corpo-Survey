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
        Schema::table('pricing_plans', function (Blueprint $table) {
            // NULL/0 means "no self-serve trial" (e.g. a contact-for-quote
            // Enterprise plan) — registering under this plan then leaves
            // organizations.trial_ends_at unset, same as before this
            // feature existed. A positive value is the number of days
            // RegisterController stamps onto the new organization's
            // trial_ends_at when this plan's id is in the registration
            // link (see PricingPlan::hasTrial()).
            $table->unsignedSmallInteger('trial_days')->nullable()->after('price_bn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });
    }
};
