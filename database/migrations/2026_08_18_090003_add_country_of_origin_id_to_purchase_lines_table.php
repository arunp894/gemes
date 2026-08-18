<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the normalized FK alongside the existing free-text
     * `country_of_origin` string (see 2026_08_17_120000). New purchase
     * lines write here via a dropdown instead; the string column is left
     * untouched for historical rows. PurchaseService::syncLines() stamps
     * this onto the product(s) it creates — see the sibling migration on
     * the products table.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->foreignId('country_of_origin_id')
                ->nullable()
                ->after('country_of_origin')
                ->constrained('countries_of_origin')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropForeign(['country_of_origin_id']);
            $table->dropColumn('country_of_origin_id');
        });
    }
};
