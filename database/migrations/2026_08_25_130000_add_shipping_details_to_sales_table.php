<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Structured shipping details for orders that need physical delivery
     * (see shipping_status). Address defaults to the customer's own
     * profile address in the UI but is editable per-sale, since a
     * delivery address can legitimately differ from the billing/profile
     * one. Tracking + date fields are filled in by staff once the order
     * actually ships.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('shipping_address_line1', 255)->nullable()->after('shipping_status');
            $table->string('shipping_address_line2', 255)->nullable()->after('shipping_address_line1');
            $table->string('shipping_city', 100)->nullable()->after('shipping_address_line2');
            $table->string('shipping_state', 100)->nullable()->after('shipping_city');
            $table->string('shipping_zip_code', 20)->nullable()->after('shipping_state');
            $table->string('shipping_country', 100)->nullable()->after('shipping_zip_code');
            $table->string('shipping_carrier', 100)->nullable()->after('shipping_country');
            $table->string('tracking_number', 100)->nullable()->after('shipping_carrier');
            $table->date('shipped_at')->nullable()->after('tracking_number');
            $table->date('estimated_delivery_date')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_address_line1',
                'shipping_address_line2',
                'shipping_city',
                'shipping_state',
                'shipping_zip_code',
                'shipping_country',
                'shipping_carrier',
                'tracking_number',
                'shipped_at',
                'estimated_delivery_date',
            ]);
        });
    }
};
