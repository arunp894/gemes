<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Packing document (header). Converts raw, unpacked purchase stock
     * into sellable Products -- see PackingService. Numbering mirrors
     * StockTransfer: PACK-YYYYMM-####, per-month global sequence.
     *
     * Lifecycle mirrors StockTransfer's state machine:
     *   draft   -> posted    (post: consumes source rows, credits the
     *                         new Product/PurchaseProduct output rows)
     *   draft   -> cancelled (no stock impact -- nothing was booked yet)
     *   posted  -> cancelled (compensating reversal -- see
     *                         StockService::reversePackingPosting())
     */
    public function up(): void
    {
        Schema::create('packings', function (Blueprint $table) {
            $table->id();
            $table->string('packing_number', 50)->unique();
            $table->date('packing_date')->index();
            $table->unsignedBigInteger('location_id')->index();

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft')
                ->index();
            $table->timestamp('posted_at')->nullable();
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
        Schema::dropIfExists('packings');
    }
};
