<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a category shows up on the storefront (homepage "Shop by
 * Collection" strip, Collections page filter bar). Independent of
 * `status` (back-office active/inactive bookkeeping only — see
 * Category::scopeActive()) and `is_gemstone` (which categories are
 * gemstone-typed at all): a category can be active and gemstone-typed
 * yet still deliberately hidden from customers.
 *
 * Defaults to true so every existing category keeps showing exactly as
 * it does today until someone explicitly hides one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_on_frontend')->default(true)->index()->after('is_gemstone');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_on_frontend');
        });
    }
};
