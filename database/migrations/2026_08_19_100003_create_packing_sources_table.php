<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Input side of a packing: which raw purchase_products rows were
     * drawn on, and how much of each. A packing can draw from several
     * raw rows (even across different purchases/lot codes), and one raw
     * row can be split across several packing outputs over time --
     * qty_taken is this packing's own claim on that row, not the row's
     * full qty.
     */
    public function up(): void
    {
        Schema::create('packing_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('packing_id')->index();
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedInteger('qty_taken');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('packing_id')
                ->references('id')->on('packings')->cascadeOnDelete();

            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_sources');
    }
};
