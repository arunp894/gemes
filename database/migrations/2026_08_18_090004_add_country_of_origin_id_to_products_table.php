<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the purchase_lines column (see the sibling migration) —
     * PurchaseService::syncLines() stamps a new row's country_of_origin_id
     * onto the Product it creates, same as every other purchase-line
     * template field. The Product edit screen also writes here directly
     * via its own dropdown.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('country_of_origin_id')
                ->nullable()
                ->after('country_of_origin')
                ->constrained('countries_of_origin')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['country_of_origin_id']);
            $table->dropColumn('country_of_origin_id');
        });
    }
};
