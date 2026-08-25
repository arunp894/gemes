<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CaratMovement — append-only CT ledger, mirroring stock_movements
     * exactly but tracking carat weight instead of piece count.
     *
     * Exists because CT is NOT evenly distributed across a row's qty
     * (a qty=3 row can be 20ct + 7ct + 3ct, not 10ct each) — qty and CT
     * are two independent quantities on the same purchase_products row,
     * each needing their own balance. This ledger is the CT equivalent
     * of stock_movements: balance = SUM(carat WHERE direction='in') -
     * SUM(carat WHERE direction='out'), computed per purchase_product_id
     * (+ optionally location_id), never a stored/mutated column.
     */
    public function up(): void
    {
        Schema::create('carat_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('location_id')->index();

            $table->enum('direction', ['in', 'out'])->index();
            $table->decimal('carat', 10, 3);

            $table->string('reason', 40)->index();
            $table->string('source_type', 40)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->unsignedBigInteger('source_line_id')->nullable();

            $table->date('movement_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_product_id', 'location_id']);
            $table->index(['source_type', 'source_id']);

            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();
            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();
            $table->foreign('location_id')
                ->references('id')->on('locations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carat_movements');
    }
};
