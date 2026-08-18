<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a "list on website" toggle to the purchase line template,
     * alongside website_price (2026_08_17_130000). When checked in the
     * Add Item form / line table, PurchaseService::syncLines() stamps
     * website_enabled = true onto every product it generates under this
     * line — and re-applies it to products the line already owns on a
     * later edit, same as carat_weight. It also promotes each affected
     * product's status to Active alongside it, since WebsiteController's
     * storefront queries require both website_enabled AND an Active
     * status to actually list a product. See syncLines() for the sync.
     *
     * Defaults to false so existing behaviour (products created as
     * Draft and website-disabled, published manually afterwards from
     * the Products screen) is unchanged unless staff opts in.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->boolean('website_enabled')->default(false)->after('website_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('website_enabled');
        });
    }
};
