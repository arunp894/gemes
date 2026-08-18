<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selling price moves to being per-ROW, not just per-line: a Box
     * line with qty 10 creates 10 individual products, and each one can
     * genuinely warrant a different listing price (different weight/
     * clarity per stone). purchase_lines.website_price (added in the
     * previous migration) is kept as the default that seeds every new
     * row's value here — same relationship carat_weight already has
     * between the two tables.
     *
     * Same precision as products.website_price (decimal 12,2).
     */
    public function up(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->decimal('website_price', 12, 2)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn('website_price');
        });
    }
};
