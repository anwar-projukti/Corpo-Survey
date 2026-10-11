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
        Schema::table('landing_contents', function (Blueprint $table) {
            // Path on the "public" disk (storage/app/public/...) to an
            // uploaded site logo, replacing the bundled public/logo-icon.png
            // everywhere <x-app-logo> renders. NULL means "use the bundled
            // default" — see LandingContent::logoUrl().
            $table->string('logo_path')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_contents', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
