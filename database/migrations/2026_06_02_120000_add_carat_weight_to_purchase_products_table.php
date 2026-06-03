<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-inventory-row carat weight captured at purchase time.
     *
     * Lives on purchase_products (one stockable unit per row) rather than
     * purchase_lines so each piece/box can record its own weight. Defaults
     * are prefilled client-side from the product's catalogue carat_weight
     * but remain editable per row. Mirrors products.carat_weight precision
     * (decimal 8,3) and is nullable for non-gemstone purchases.
     */
    public function up(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('carat_weight', 8, 3)->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn('carat_weight');
        });
    }
};
