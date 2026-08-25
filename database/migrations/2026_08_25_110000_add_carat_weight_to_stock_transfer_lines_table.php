<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carat actually being moved on this line. Defaults from the piece's
     * recorded purchase_products.carat_weight but stays independently
     * editable when the row holds more than one unit — a partial split
     * (moving 2 of 3 identical-ish stones) may not carry exactly the
     * per-unit average. Same precision as purchase_products.carat_weight.
     */
    public function up(): void
    {
        Schema::table('stock_transfer_lines', function (Blueprint $table) {
            $table->decimal('carat_weight', 8, 3)->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_lines', function (Blueprint $table) {
            $table->dropColumn('carat_weight');
        });
    }
};
