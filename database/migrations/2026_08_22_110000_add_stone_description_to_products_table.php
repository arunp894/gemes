<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeform narrative field for gemstone products -- inclusions,
     * brilliance, provenance notes, anything the structured grade fields
     * (colour_grade, clarity_grade, cut_shape, treatment) don't capture.
     * Mirrors the purchase_lines column (see sibling migration);
     * PurchaseService::syncLines() stamps a new row's stone_description
     * onto the Product it creates, same as every other purchase-line
     * template field. The Product edit screen also writes here directly
     * via its own textarea.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('stone_description')->nullable()->after('treatment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stone_description');
        });
    }
};
