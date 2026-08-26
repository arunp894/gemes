<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional category ("Stone") scope for a stock audit.
     *
     * Null means the audit covers every category at its location - the
     * original, unscoped behaviour. When set, the audit's snapshot is
     * limited to on-hand pieces whose product belongs to this category
     * - see StockAuditService::start() and
     * StockService::onHandPiecesForLocation().
     *
     * nullOnDelete (not restrictOnDelete, unlike location_id) because a
     * category can be soft/hard-deleted long after an audit is closed
     * out; the historical audit record should survive that, just losing
     * its category label (falls back to "All Stones" in the UI).
     */
    public function up(): void
    {
        Schema::table('stock_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('location_id')->index();

            $table->foreign('category_id')
                ->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_audits', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
