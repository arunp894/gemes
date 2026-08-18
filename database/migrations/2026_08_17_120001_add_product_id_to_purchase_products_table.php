<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The column that makes "one purchase_product row = one Product"
     * real. Nullable + nullOnDelete: historical rows (pre-this-migration)
     * have none and keep working through their line's old product_id;
     * if a linked product is ever hard-deleted, this row survives with
     * the link set to null rather than being blocked or destroyed — the
     * ledger/lot-code/cost history on the row stays intact either way.
     */
    public function up(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->index()->after('purchase_line_id');

            $table->foreign('product_id')
                ->references('id')->on('products')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
