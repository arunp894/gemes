<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moves "show on website" + Selling Price from a single toggle
     * covering the whole packing (the old packings.website_enabled-esque
     * form field, never actually a column) to a per-ROW choice, same
     * granularity purchase_products.website_price already applies at
     * for raw stock. Living on packing_sources (the input/claim side)
     * rather than the output purchase_products row means the values
     * round-trip cleanly onto the edit screen keyed by the same row the
     * user configured, with no fragile order-matching against outputs
     * needed -- PackingService::syncOutputsFromSources() reads them
     * straight off the source when it (re)builds each output.
     *
     * website_price defaults from the raw piece's own website_price
     * (itself seeded from purchase_lines.website_price at purchase
     * entry) when the packing form doesn't override it; website_enabled
     * defaults from the raw piece's purchase line's own website_enabled
     * hint. See PackingService::syncSources().
     */
    public function up(): void
    {
        Schema::table('packing_sources', function (Blueprint $table) {
            $table->boolean('website_enabled')->default(false)->after('qty_taken');
            $table->decimal('website_price', 12, 2)->nullable()->after('website_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('packing_sources', function (Blueprint $table) {
            $table->dropColumn(['website_enabled', 'website_price']);
        });
    }
};
