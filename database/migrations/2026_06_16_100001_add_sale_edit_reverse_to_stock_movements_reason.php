<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extend the stock_movements.reason ENUM with 'sale_edit_reverse'.
 *
 * When a POSTED/COMPLETED sale is edited within its window, the previous
 * OUT movements are reversed with an IN row carrying this reason before
 * the rebuilt lines are re-booked (see StockService::reverseSaleForEdit).
 *
 * Laravel's schema builder can't ALTER an ENUM in place, so we use a raw
 * MODIFY COLUMN statement that re-declares the full value list.
 */
return new class extends Migration
{
    private array $reasons = [
        'purchase',
        'purchase_cancel',
        'sale',
        'sale_return',
        'sale_cancel',
        'sale_edit_reverse',     // NEW — IN: stock returned when a posted sale is edited
        'transfer_out',
        'transfer_in',
        'transfer_cancel_out',
        'adjustment_in',
        'adjustment_out',
        'opening',
    ];

    public function up(): void
    {
        $values = $this->enumList($this->reasons);

        DB::statement(
            "ALTER TABLE `stock_movements` MODIFY COLUMN `reason` ENUM($values) NOT NULL"
        );
    }

    public function down(): void
    {
        // Roll back to the original list (without 'sale_edit_reverse').
        $original = array_values(array_filter(
            $this->reasons,
            fn ($r) => $r !== 'sale_edit_reverse'
        ));

        $values = $this->enumList($original);

        DB::statement(
            "ALTER TABLE `stock_movements` MODIFY COLUMN `reason` ENUM($values) NOT NULL"
        );
    }

    private function enumList(array $values): string
    {
        return implode(',', array_map(fn ($v) => "'" . $v . "'", $values));
    }
};
