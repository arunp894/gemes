<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stock ledger. Append-only by design — every movement is a new
     * row. Balances are derived: SUM(direction='in') − SUM(direction='out')
     * filtered by purchase_product_id + location_id.
     *
     * Corrections are made by inserting compensating rows (a reversing
     * OUT to cancel an IN, etc.) — never by UPDATE or DELETE on this
     * table. SoftDeletes is present only to satisfy the project-wide
     * convention; production code should NOT delete movements.
     *
     * The source_* columns form a polymorphic-ish backlink to the
     * originating document (Purchase, Sale, StockTransfer, StockAdjustment).
     * We don't use Laravel's polymorphic relations because we want
     * indexable, queryable FK-like columns.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // ── What moved ──────────────────────────────────────────────
            // Per-piece tracking — the canonical "which item" is the
            // purchase_products row. product_id is denormalized for fast
            // per-product reporting without joining via purchase_lines.
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            // ── Where ───────────────────────────────────────────────────
            $table->unsignedBigInteger('location_id')->index();

            // ── Direction + quantity ────────────────────────────────────
            // direction is enum to keep the running-sum query simple:
            //   SUM(CASE WHEN direction='in' THEN qty ELSE -qty END)
            $table->enum('direction', ['in', 'out'])->index();

            // Always positive; sign is carried by `direction`.
            $table->unsignedInteger('qty');

            // ── Why ─────────────────────────────────────────────────────
            // Semantic reason, lets reports group by movement cause.
            // Adding a new reason? Append here AND to StockMovement::REASONS.
            $table->enum('reason', [
                'purchase',              // IN  — purchase posted
                'purchase_cancel',       // OUT — posted purchase cancelled
                'sale',                  // OUT — sale posted
                'sale_return',           // IN  — sale refunded
                'sale_cancel',           // IN  — posted sale cancelled
                'transfer_out',          // OUT — transfer left source location
                'transfer_in',           // IN  — transfer arrived at destination
                'transfer_cancel_out',   // IN  — in-transit transfer cancelled (returns to source)
                'adjustment_in',         // IN  — manual positive adjustment
                'adjustment_out',        // OUT — manual negative adjustment
                'opening',               // IN  — opening stock seed
            ])->index();

            // ── Backlinks to source document (denormalized polymorphic) ─
            // source_type names match the model basename in lowercase for
            // sanity: purchase | sale | stock_transfer | stock_adjustment.
            $table->string('source_type', 30)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            // Optional: the specific line-row inside the source doc, for
            // exact traceability (e.g. which sale_line consumed this piece).
            $table->unsignedBigInteger('source_line_id')->nullable();

            // ── Optional rack snapshot ──────────────────────────────────
            // Helps when a piece sits in a specific rack at the location.
            $table->unsignedBigInteger('rack_id')->nullable()->index();

            // ── Booking ─────────────────────────────────────────────────
            $table->date('movement_date')->index();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Foreign keys ────────────────────────────────────────────
            // RESTRICT so we never lose the link from a movement back to
            // the piece it referenced. If a piece must go, the movements
            // referencing it must be reversed first.
            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')->on('locations')->restrictOnDelete();

            $table->foreign('rack_id')
                ->references('id')->on('racks')->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            // ── Composite index for the hottest query ───────────────────
            // SELECT balance FROM stock_movements
            //   WHERE purchase_product_id = ? AND location_id = ?
            $table->index(['purchase_product_id', 'location_id'], 'idx_piece_location');

            // Product-at-location aggregate query is also common.
            $table->index(['product_id', 'location_id'], 'idx_product_location');

            // Source lookup ("show all movements for this purchase").
            $table->index(['source_type', 'source_id'], 'idx_source_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
