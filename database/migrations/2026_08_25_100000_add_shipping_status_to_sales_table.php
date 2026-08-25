<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fulfillment tracking for orders that actually need physical shipping.
     * Null means "not applicable" — a POS walk-in sale, for instance, where
     * the customer leaves with the item and there's nothing to ship.
     * SaleService/CheckoutController set this to 'pending' at creation time
     * for website-channel sales; staff advance it from there.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('shipping_status', 20)->nullable()->after('shipping_charge');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('shipping_status');
        });
    }
};
