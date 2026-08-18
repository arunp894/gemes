<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase payments. Multiple rows per purchase enable partial /
     * advance payments and split-tender (e.g. half cash + half bank
     * transfer) to a supplier. The Purchase header's paid_amount /
     * due_amount / payment_status are denormalized aggregates kept in
     * sync by PurchaseService::recalculatePayments(). Mirrors
     * sale_payments exactly — see that migration for the same shape.
     *
     * payment_method is a small enum kept narrow for reporting:
     *   cash | card | upi | bank_transfer | cheque | other
     *
     * For supplier refunds/credits, a negative `amount` row can be
     * inserted — the aggregation still works correctly.
     */
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_id')->index();

            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('payment_method', [
                'cash', 'card', 'upi', 'bank_transfer', 'cheque', 'other',
            ])->default('cash')->index();

            // Transaction id, UPI ref, cheque number, etc.
            $table->string('reference_number', 100)->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_id')
                ->references('id')->on('purchases')->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
