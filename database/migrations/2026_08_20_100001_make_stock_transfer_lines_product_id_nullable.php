<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stock transfers could only ever move a PACKED piece (one with a
     * resolvable product) -- StockTransferService::syncLines() flatly
     * refused any purchase_products row with no product_id, so raw
     * (unpacked) purchase stock could never be transferred between
     * locations at all. product_id needs to accept NULL so a transfer
     * line can name a raw row instead -- purchase_product_id stays
     * mandatory either way; every line still names the exact piece
     * moving. Same convention stock_movements.product_id already uses
     * for this (2026_08_19_100000) and the same reasoning: a Product
     * only exists once raw stock is packed (see PackingService).
     *
     * ->nullable()->change() needs doctrine/dbal, which isn't installed
     * here -- same raw-ALTER approach as stock_movements.product_id.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE stock_transfer_lines MODIFY COLUMN product_id BIGINT UNSIGNED NULL');
    }

    /**
     * Only safe to run down() on an environment with no NULL product_id
     * rows written under the new regime -- they'd violate the NOT NULL
     * this restores.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE stock_transfer_lines MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');
    }
};
