<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * THE inventory table. Each row IS one stockable unit:
     *   - For piece-type products  : ONE row whose qty is the count purchased.
     *   - For unit/carton products : ONE row PER inner pack (e.g. one row
     *     per Box), each with qty = unit_contains, its own barcode, rack,
     *     expiry, etc.
     *
     * This shape makes stock queries cheap: "how many on hand" is just
     * SUM(qty) of purchase_products joined to posted purchases minus any
     * downstream consumption ledger.
     *
     * barcode is stored verbatim (not FK'd to the `barcodes` table) so
     * scanned-but-unregistered barcodes on warehouse receipt aren't blocked
     * — the application can reconcile them post-hoc.
     */
    public function up(): void
    {
        Schema::create('purchase_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_line_id')->index();

            $table->unsignedInteger('qty')->default(1);
            $table->string('barcode', 100)->nullable()->index();
            $table->unsignedBigInteger('rack_id')->nullable()->index();
            $table->string('serial_number', 100)->nullable();

            $table->decimal('price',            15, 2)->default(0);
            $table->decimal('tax_percent',       5, 2)->default(0);
            $table->decimal('tax_amount',       15, 2)->default(0);
            $table->decimal('discount_percent',  5, 2)->default(0);
            $table->decimal('discount_amount',  15, 2)->default(0);

            $table->date('expiry_date')->nullable();
            $table->date('manufacture_date')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_line_id')
                ->references('id')->on('purchase_lines')->cascadeOnDelete();

            $table->foreign('rack_id')
                ->references('id')->on('racks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_products');
    }
};
