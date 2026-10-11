<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the webhooks feature (org-scoped outbound event notifications)
 * entirely, per product decision — not a redesign, a removal. Drops
 * webhook_deliveries before webhooks since it holds the foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }

    public function down(): void
    {
        // Deliberately not reversible — the feature and its migrations
        // that created these tables are gone, so there's nothing to
        // recreate them from.
    }
};
