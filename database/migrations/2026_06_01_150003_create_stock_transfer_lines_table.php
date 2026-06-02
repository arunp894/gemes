<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock transfer line items. Per-piece, like the rest of the
     * inventory system — each row names the exact piece being moved.
     *
     * qty is small (usually 1) for gemstone work but kept as int to
     * support fungible-product transfers.
     */
    public function up(): void
    {
        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_transfer_id')->index();
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            $table->unsignedInteger('qty')->default(1);

            // Optional override for the destination rack. NULL means
            // "place at the to_location's default rack" or "unassigned".
            $table->unsignedBigInteger('to_rack_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stock_transfer_id')
                ->references('id')->on('stock_transfers')->cascadeOnDelete();

            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();

            $table->foreign('to_rack_id')
                ->references('id')->on('racks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
    }
};
