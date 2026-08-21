<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds pack_in / pack_out / pack_cancel / pack_restore to
     * stock_movements.reason.
     *
     *   pack_out     : OUT -- raw source row consumed into a packing
     *   pack_in      : IN  -- new sellable output row credited
     *   pack_cancel  : OUT -- reversal of pack_in (packing cancelled)
     *   pack_restore : IN  -- reversal of pack_out (packing cancelled)
     *
     * Cancel/restore get their own reasons rather than reusing pack_in/
     * pack_out so hasMovementsFromSource()-style idempotency guards stay
     * unambiguous between "originally posted" and "reversed".
     *
     * Same raw-ALTER approach as
     * 2026_06_16_100001_add_sale_edit_reverse_to_stock_movements_reason.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN reason ENUM(
            'purchase',
            'purchase_cancel',
            'sale',
            'sale_return',
            'sale_cancel',
            'sale_edit_reverse',
            'transfer_out',
            'transfer_in',
            'transfer_cancel_out',
            'adjustment_in',
            'adjustment_out',
            'opening',
            'pack_in',
            'pack_out',
            'pack_cancel',
            'pack_restore'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN reason ENUM(
            'purchase',
            'purchase_cancel',
            'sale',
            'sale_return',
            'sale_cancel',
            'sale_edit_reverse',
            'transfer_out',
            'transfer_in',
            'transfer_cancel_out',
            'adjustment_in',
            'adjustment_out',
            'opening'
        ) NOT NULL");
    }
};
