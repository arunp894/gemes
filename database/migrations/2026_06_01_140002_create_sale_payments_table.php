<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sale payments. Multiple rows per sale enable partial payments and
     * split-tender (e.g. half cash + half UPI). The Sale header's
     * paid_amount / balance_due / payment_status are denormalized
     * aggregates kept in sync by SaleService::recalculatePayments().
     *
     * payment_method is a small enum kept narrow for reporting:
     *   cash | card | upi | bank_transfer | cheque | other
     *
     * For refunds, a negative `amount` row can be inserted — the
     * aggregation still works correctly.
     */
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id')->index();

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

            $table->foreign('sale_id')
                ->references('id')->on('sales')->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
