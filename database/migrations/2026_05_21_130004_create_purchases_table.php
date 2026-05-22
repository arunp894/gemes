<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Invoice-level (header) table. Holds the supplier, totals, tax model
     * and payment status. No warehouse_id (per project decision: single
     * location).
     *
     * tax_type drives the GST split on the printed invoice:
     *   - none      : no tax line
     *   - cgst_sgst : tax_total split 50/50 into CGST + SGST
     *   - igst      : entire tax_total shown as IGST
     *
     * status:
     *   - draft     : in progress, no stock impact
     *   - posted    : finalised, inventory live (purchase_products rows
     *                 are then treated as on-hand stock)
     *   - cancelled : reversed; rows remain for audit but excluded from
     *                 stock calculations
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->date('purchase_date')->index();
            $table->unsignedBigInteger('supplier_id')->index();

            $table->enum('tax_type', ['none', 'cgst_sgst', 'igst'])
                ->default('none');

            // Money fields — decimal(15,2) gives plenty of headroom.
            $table->decimal('subtotal',       15, 2)->default(0);
            $table->decimal('tax_total',      15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('grand_total',    15, 2)->default(0);
            $table->decimal('paid_amount',    15, 2)->default(0);
            $table->decimal('due_amount',     15, 2)->default(0);

            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft')
                ->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FK: suppliers -> RESTRICT (don't lose history if supplier is
            // hard-deleted by mistake; soft delete is the safe path).
            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
