<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock audit (physical stock-take) document header.
     *
     * Numbering: AUD-YYYYMM-#### — same shape as StockTransfer's
     * TRF-YYYYMM-####. Generated inside StockAuditService::start().
     *
     * Lifecycle:
     *   in_progress → completed   (finalize — no ledger writes by itself)
     *   in_progress → cancelled   (abandon — no ledger writes)
     *   completed / cancelled are terminal.
     *
     * expected_total / matched_total are running counters maintained by
     * StockAuditService so the scan screen's progress bar never needs a
     * COUNT(*) over stock_audit_items for a 10k+ item location.
     */
    public function up(): void
    {
        Schema::create('stock_audits', function (Blueprint $table) {
            $table->id();

            $table->string('audit_number', 50)->unique();
            $table->date('audit_date')->index();

            $table->unsignedBigInteger('location_id')->index();

            $table->enum('status', ['in_progress', 'completed', 'cancelled'])
                ->default('in_progress')
                ->index();

            $table->unsignedInteger('expected_total')->default(0);
            $table->unsignedInteger('matched_total')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('location_id')
                ->references('id')->on('locations')->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_audits');
    }
};
