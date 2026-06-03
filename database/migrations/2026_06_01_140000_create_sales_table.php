<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales invoice (header).
     *
     * Numbering: SALE-YYYYMM-#### — global sequence per month, padded to
     * 4 digits. The generator runs inside the SaleService transaction so
     * concurrent saves can't collide.
     *
     * status lifecycle:
     *   - draft     : being built in the terminal, no stock impact
     *   - posted    : finalised — sale_lines treated as out-going stock
     *   - completed : fully paid + delivered (terminal status for clean sales)
     *   - refunded  : reversed after posting; lines remain for audit
     *   - cancelled : voided pre-completion
     *
     * payment_status is derived from sale_payments but stored for fast
     * listing/filtering: unpaid / partial / paid.
     *
     * paid_amount / balance_due are denormalized from sale_payments and
     * recomputed by SaleService whenever payments change.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->string('sale_number', 50)->unique();
            $table->date('sale_date')->index();

            // ── Parties ──────────────────────────────────────────────────
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('salesperson_id')->nullable()->index();

            // ── Tax model ────────────────────────────────────────────────
            $table->enum('tax_type', ['none', 'cgst_sgst', 'igst'])->default('none');

            // ── Money fields ─────────────────────────────────────────────
            $table->decimal('subtotal',        15, 2)->default(0);
            $table->decimal('tax_total',       15, 2)->default(0);
            $table->decimal('discount_total',  15, 2)->default(0);
            $table->decimal('shipping_charge', 15, 2)->default(0);
            $table->decimal('grand_total',     15, 2)->default(0);
            $table->decimal('paid_amount',     15, 2)->default(0);
            $table->decimal('balance_due',     15, 2)->default(0);

            // ── Statuses ─────────────────────────────────────────────────
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])
                ->default('unpaid')
                ->index();

            $table->enum('status', ['draft', 'posted', 'completed', 'refunded', 'cancelled'])
                ->default('draft')
                ->index();

            $table->text('note')->nullable();

            // ── Audit ────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Foreign keys ─────────────────────────────────────────────
            // customer + location: restrict — never lose the link from a
            // sale. If a customer or location must be removed, the sale
            // must be soft-deleted first.
            $table->foreign('customer_id')
                ->references('id')->on('customers')->restrictOnDelete();

            $table->foreign('location_id')
                ->references('id')->on('locations')->restrictOnDelete();

            $table->foreign('salesperson_id')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
