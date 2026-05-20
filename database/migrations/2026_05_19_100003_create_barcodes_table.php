<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Barcodes table. Supports both Single-Barcode and Multi-Barcode modes
     * by having a hasMany relationship with products. The "mode" is implicit:
     *   - 1 row for the product   => Single mode
     *   - 2..20 rows for product  => Multi mode
     *
     * Business rules enforced at app level (not DB):
     *   - Exactly one row per product must have is_primary = true.
     *   - Switching back to single mode is only allowed when 1 row remains.
     *
     * barcode_value is UNIQUE at DB level — platform-wide, including across
     * soft-deleted rows. Once a barcode value is used, it can never be reused
     * (physical world identifier safety).
     */
    public function up(): void
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('barcode_value', 100)->unique();
            $table->string('barcode_format', 20);
            $table->string('barcode_label', 100)->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->integer('sequence_number')->default(1);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
