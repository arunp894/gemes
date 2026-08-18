<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a selling-price field to the purchase line template. Named
     * website_price (not selling_price) to exactly match its counterpart
     * on products — every other template field on this table already
     * mirrors the Product column it stamps onto (carat_weight,
     * stone_type, country_of_origin, ...), and this keeps that
     * convention intact.
     *
     * Nullable and optional: staff can leave it blank at purchase time
     * and set the actual listing price later from the product's own
     * edit screen, same as any other product field not captured here.
     *
     * Same precision as products.website_price (decimal 12,2) so a
     * value copied straight across never gets truncated.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->decimal('website_price', 12, 2)->nullable()->after('notes_tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('website_price');
        });
    }
};
