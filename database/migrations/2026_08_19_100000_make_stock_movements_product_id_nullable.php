<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Raw (unpacked) purchase stock now posts to the ledger with no
     * retail Product yet -- a Product is only created once that stock is
     * packed (see PackingService). product_id has to accept NULL to
     * carry those IN/OUT movements. purchase_product_id stays mandatory
     * -- every movement still names the exact row that moved.
     *
     * ->nullable()->change() needs doctrine/dbal, which isn't installed
     * here -- same raw-ALTER approach already used for
     * purchase_lines.product_id (2026_08_17_120000) and the reason enum
     * (2026_06_16_100001).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN product_id BIGINT UNSIGNED NULL');
    }

    /**
     * Only safe to run down() on an environment with no NULL product_id
     * rows written under the new regime -- they'd violate the NOT NULL
     * this restores.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');
    }
};
