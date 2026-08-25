<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Carat actually sold on this line. Defaults from the piece's
     * purchase-time carat_weight (PurchaseService already records that on
     * purchase_products) but is independently editable at the point of
     * sale — a re-weigh at the counter can differ slightly from the
     * purchase-time figure. Same precision as purchase_products.carat_weight.
     */
    public function up(): void
    {
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->decimal('carat_weight', 8, 3)->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->dropColumn('carat_weight');
        });
    }
};
