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
        Schema::table('users', function (Blueprint $table) {
            // Nullable: a Super Admin belongs to no organization.
            $table->foreignId('organization_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->string('status')->default('active')->after('password'); // active | suspended
            $table->string('locale', 5)->default('en')->after('status'); // en | bn
            $table->string('theme_preference', 10)->default('system')->after('locale'); // light | dark | system
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['status', 'locale', 'theme_preference', 'deleted_at']);
        });
    }
};
