<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Channels lookup table. Holds the sales channels a barcode can be
     * assigned to: eBay, Catawiki, Website, Sukainagems. Seeded by
     * ChannelSeeder. Kept as a separate table (not enum) so new channels
     * can be added without a schema change.
     */
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('code', 30)->unique();
            $table->string('icon', 50)->nullable();
            $table->boolean('status')->default(true)->index();
            $table->integer('display_order')->default(0)->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('channels');
    }
};
