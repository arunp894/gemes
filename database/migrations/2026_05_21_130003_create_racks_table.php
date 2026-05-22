<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Racks (storage bins). Each `purchase_products` row may reference one
     * rack to record where the physical box/piece is stocked in the
     * warehouse. Codes are unique; `nullOnDelete` is used on the FK side
     * (purchase_products.rack_id) so deleting a rack doesn't strand the
     * inventory row.
     */
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('location', 200)->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true)->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
