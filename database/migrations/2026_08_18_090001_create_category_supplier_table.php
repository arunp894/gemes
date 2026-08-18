<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table for the Supplier <-> Category many-to-many mapping.
     * A supplier can deal in several categories; the purchase create/edit
     * screen uses this (via SupplierController::categories()) to filter
     * the "Add Item" category dropdown down to whatever the chosen
     * supplier actually supplies, instead of showing every category.
     *
     * No soft deletes here — this is a pure relationship table, not an
     * entity in its own right (mirrors barcode_channel / permission_role).
     */
    public function up(): void
    {
        Schema::create('category_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_supplier');
    }
};
