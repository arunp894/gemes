<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sale line items. ONE row per product-line on the sale invoice.
     *
     * Simpler than purchase_lines: sales typically move individual items
     * (especially for gemstones) so there's no inner-pack expansion
     * layer. If a sale needs to move N identical pieces, qty handles it.
     *
     * purchase_product_id (optional) links the sold piece back to the
     * specific inventory row it came from. When the barcode scanner
     * matches an exact PurchaseProduct.barcode, the service will link
     * them and snapshot cost_price for margin reporting.
     */
    public function up(): void
    {
        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            // Optional link to the exact inventory unit (purchase_products row).
            // Lets us compute margin and decrement stock with precision.
            $table->unsignedBigInteger('purchase_product_id')->nullable()->index();

            // The scanned barcode value at the moment of sale. Kept as
            // plain text so unregistered barcodes don't block checkout.
            $table->string('barcode', 100)->nullable()->index();

            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);

            $table->decimal('tax_percent',       5, 2)->default(0);
            $table->decimal('tax_amount',       15, 2)->default(0);
            $table->decimal('discount_percent',  5, 2)->default(0);
            $table->decimal('discount_amount',  15, 2)->default(0);

            // subtotal = qty × unit_price
            // total    = subtotal − discount_amount + tax_amount
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total',    15, 2)->default(0);

            // Cost snapshot — captured from the linked PurchaseProduct
            // at sale time. Frozen here so future cost changes don't
            // retro-affect historical margin reports.
            $table->decimal('cost_price', 15, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sale_id')
                ->references('id')->on('sales')->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();

            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
    }
};
