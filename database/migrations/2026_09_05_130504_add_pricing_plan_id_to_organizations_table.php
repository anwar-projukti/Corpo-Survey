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
        Schema::table('organizations', function (Blueprint $table) {
            // Which pricing plan this organization registered under (see
            // RegisterController's ?plan=<id> link from the landing page's
            // pricing cards) — shown in the sidebar alongside the role
            // label so an Org Admin can see their plan and trial status at
            // a glance. Null for organizations that registered without a
            // plan link, or ones that predate this column. nullOnDelete
            // rather than cascading: deleting a pricing plan from
            // /pricing-plans should never take an organization down with
            // it — the org just loses its plan reference.
            $table->foreignId('pricing_plan_id')->nullable()->after('trial_ends_at')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_plan_id');
        });
    }
};
