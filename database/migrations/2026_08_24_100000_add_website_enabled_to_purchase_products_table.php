<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Website visibility moves to being per-ROW, not just per-line: a Box
     * line with qty 10 creates 10 individual products, and only some of
     * them may be ready to list. purchase_lines.website_enabled is kept
     * as the default that seeds every new row's value here — same
     * relationship website_price already has between the two tables.
     */
    public function up(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->boolean('website_enabled')->default(false)->after('website_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropColumn('website_enabled');
        });
    }
};
