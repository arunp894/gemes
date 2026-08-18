<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a boolean `is_gemstone` flag to the categories table.
     *
     * When ticked, products in that category will see the Gemstone Details
     * panel on the product form, and validation will require
     * carat_weight / stone_type / treatment.
     *
     * Replaces the previous design that relied on a hard-coded list of
     * category codes (Product::GEMSTONE_PARENT_CODES). The flag lets
     * admins mark any category as gemstone-type without editing PHP.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_gemstone')
                ->default(false)
                ->index()
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_gemstone']);
            $table->dropColumn('is_gemstone');
        });
    }
};
