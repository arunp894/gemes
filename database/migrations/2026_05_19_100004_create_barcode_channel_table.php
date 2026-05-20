<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table for the many-to-many between barcodes and channels.
     * If a barcode has NO rows here, it is implicitly available to ALL
     * channels (per spec §5.2: "If left blank, the barcode is available
     * across all channels"). Presence of any rows means the barcode is
     * restricted to ONLY those channels.
     */
    public function up(): void
    {
        Schema::create('barcode_channel', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barcode_id');
            $table->unsignedBigInteger('channel_id');
            $table->timestamps();

            $table->foreign('barcode_id')
                ->references('id')
                ->on('barcodes')
                ->cascadeOnDelete();

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->cascadeOnDelete();

            $table->unique(['barcode_id', 'channel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcode_channel');
    }
};
