<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The Basic plan (seeded in 2026_09_05_084056_seed_default_pricing_plans)
        // gets a 30-day trial by default — editable per-plan from
        // /pricing-plans like everything else on it. Enterprise stays
        // without one (it's the contact-for-quote plan).
        DB::table('pricing_plans')
            ->where('name_en', 'Basic')
            ->update(['trial_days' => 30]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pricing_plans')
            ->where('name_en', 'Basic')
            ->update(['trial_days' => null]);
    }
};
