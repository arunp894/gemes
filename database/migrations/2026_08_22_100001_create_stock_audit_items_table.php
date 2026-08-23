<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Frozen snapshot of every piece with positive on-hand balance at an
     * audit's location, taken the moment the audit starts (see
     * StockService::onHandPiecesForLocation() /
     * StockAuditService::start()). Scanning during the audit only ever
     * matches against this snapshot, so sales/transfers happening
     * elsewhere while the floor is being counted can't move the goalposts.
     *
     * lot_code is the primary scan-match key — it's what the printed
     * shelf label actually encodes as a barcode (see
     * resources/views/purchases/labels.blade.php, which renders
     * JsBarcode over purchase_products.lot_code). `barcode` (the
     * receiving-dock value, which can repeat across a box's rows) is
     * kept as a fallback match and for report display only.
     *
     * matched_at stamped the instant a scan resolves to this row —
     * still-null rows once the audit is completed ARE the "missing
     * stock" report.
     */
    public function up(): void
    {
        Schema::create('stock_audit_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_audit_id')->index();
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            // Denormalized at snapshot time so scan lookups and the
            // report never need to join purchase_products on the hot path.
            $table->string('lot_code', 30)->nullable()->index();
            $table->string('barcode', 100)->nullable()->index();

            $table->timestamp('matched_at')->nullable();
            $table->unsignedBigInteger('matched_by')->nullable();

            // Set once the "write off missing stock" action has booked a
            // compensating stock_movements OUT for this row — prevents a
            // second pass from double-adjusting.
            $table->timestamp('written_off_at')->nullable();
            $table->unsignedBigInteger('written_off_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['stock_audit_id', 'purchase_product_id'], 'uniq_audit_piece');

            $table->foreign('stock_audit_id')
                ->references('id')->on('stock_audits')->cascadeOnDelete();

            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();

            $table->foreign('matched_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('written_off_by')
                ->references('id')->on('users')->nullOnDelete();

            // Hottest query: find the (one) unmatched row for this
            // audit + scanned value.
            $table->index(['stock_audit_id', 'lot_code'], 'idx_audit_lotcode');
            $table->index(['stock_audit_id', 'barcode'], 'idx_audit_barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_audit_items');
    }
};
