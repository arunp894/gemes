<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A purchase_products row now has two possible origins:
     *   - purchase_line_id : raw stock received on a purchase (existing
     *                        column, now nullable).
     *   - packing_id       : a sellable pack created by PackingService,
     *                        consuming one or more raw rows above.
     * Exactly one is set per row going forward -- an app-level
     * invariant (enforced in PurchaseService::syncLines() /
     * PackingService), not a DB constraint, same convention as the
     * StockMovement append-only rule.
     *
     * ->nullable()->change() needs doctrine/dbal, which isn't installed
     * -- same raw-ALTER approach as purchase_lines.product_id
     * (2026_08_17_120000).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE purchase_products MODIFY COLUMN purchase_line_id BIGINT UNSIGNED NULL');

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->unsignedBigInteger('packing_id')->nullable()->index()->after('purchase_line_id');

            $table->foreign('packing_id')
                ->references('id')->on('packings')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropForeign(['packing_id']);
            $table->dropColumn('packing_id');
        });

        DB::statement('ALTER TABLE purchase_products MODIFY COLUMN purchase_line_id BIGINT UNSIGNED NOT NULL');
    }
};
