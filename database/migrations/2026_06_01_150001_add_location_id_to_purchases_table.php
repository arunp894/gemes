<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Purchases need a location. When inventory becomes per-location, we
     * must know where a purchase lands. Currently no purchases have one.
     *
     * Strategy:
     *   1. Add column nullable
     *   2. Backfill existing rows to the default location (or earliest
     *      created location if no default exists)
     *   3. New code in StorePurchaseRequest will require it
     *
     * We don't tighten to NOT NULL at the DB level here — leaving it
     * nullable keeps soft-deleted/legacy rows valid. The PurchaseService
     * resolves nulls to the default location at posting time as a safety
     * net.
     */
    /**
     * Purchases are single-location — no location_id column needed.
     * This migration is intentionally a no-op, kept so the migration
     * history remains intact.
     */
    public function up(): void
    {
        // No-op: purchases are not location-based.
    }

    public function down(): void
    {
        // No-op.
    }
};
