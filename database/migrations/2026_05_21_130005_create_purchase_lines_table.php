<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product-level summary on a purchase. ONE line per product per
     * purchase. The `type` column copies the product's pack_type at the
     * moment of purchase (so future changes to the product's packaging
     * don't retro-affect historical invoices).
     *
     * Field semantics:
     *   - package_qty    : how many of the chosen pack the user entered
     *                      (e.g. "2" cartons or "5" pieces)
     *   - total_qty      : pieces in total (piece: package_qty,
     *                      unit: package_qty * inner_contains,
     *                      carton: package_qty * outer_contains * inner_contains)
     *   - unit_contains  : pieces per inner pack (NULL for piece type)
     *   - package_name   : e.g. "Box" — the leaf inventory unit's display label
     */
    public function up(): void
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->index();
            $table->unsignedBigInteger('product_id')->index();

            $table->enum('type', ['piece', 'unit', 'carton']);
            $table->string('package_name', 50)->nullable();
            $table->unsignedInteger('package_qty')->default(1);
            $table->unsignedInteger('total_qty')->default(0);
            $table->unsignedInteger('unit_contains')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total',    15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_id')
                ->references('id')->on('purchases')->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_lines');
    }
};
