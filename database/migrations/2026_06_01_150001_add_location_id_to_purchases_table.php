<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add location_id to purchases so each purchase is tied to the
     * physical location where the stock will land on posting.
     *
     * Strategy:
     *   1. Add column nullable (idempotent — skips if column already exists)
     *   2. Backfill existing rows to the default / oldest location
     *   3. Add FK after backfill to avoid FK violation during UPDATE
     *
     * Column is left nullable at DB level so legacy / soft-deleted rows
     * without a location remain valid. PurchaseService resolves NULL to
     * the default location at posting time as a safety net.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('purchases', 'location_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->after('supplier_id')->index();
            });
        }

        // Backfill existing purchases that have no location_id yet.
        $defaultLocationId = DB::table('locations')
            ->whereNull('deleted_at')
            ->where('is_default', true)
            ->value('id');

        if (! $defaultLocationId) {
            $defaultLocationId = DB::table('locations')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->value('id');
        }

        if ($defaultLocationId) {
            DB::table('purchases')
                ->whereNull('location_id')
                ->update(['location_id' => $defaultLocationId]);
        }

        // Add FK only if it doesn't exist yet.
        $fkExists = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'purchases'
               AND CONSTRAINT_NAME = 'purchases_location_id_foreign'"
        ))->isNotEmpty();

        if (! $fkExists) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreign('location_id')
                    ->references('id')->on('locations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
