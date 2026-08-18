<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup table for gemstone country of origin. Replaces the free-text
     * `country_of_origin` string previously typed on purchase lines and
     * products (see the two follow-up migrations that add
     * country_of_origin_id next to it) with a managed, consistent list —
     * same shape as Channel: simple, no hierarchy, no media.
     *
     * The old string columns are left in place rather than dropped,
     * matching this project's established convention of not backfilling
     * or removing superseded columns (see the product_id-on-purchase_lines
     * migration for precedent).
     */
    public function up(): void
    {
        Schema::create('countries_of_origin', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('status')->default(true)->index();
            $table->unsignedSmallInteger('display_order')->default(0)->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries_of_origin');
    }
};
