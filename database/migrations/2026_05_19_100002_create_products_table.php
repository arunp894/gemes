<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Products table. category_id points to a LEAF category (i.e. a record
     * in `categories` whose parent_id is NOT null — what the spec calls a
     * "subcategory"). The top-level category is derivable via the
     * relationship and is denormalised only on the Product model accessors.
     *
     * Status convention mirrors Category: boolean column, 0 = Draft, 1 = Active.
     * Gemstone-specific fields are all nullable; they are populated only when
     * the chosen subcategory belongs to a gemstone-family parent.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            /* ----------------------------- Core ----------------------------- */
            $table->string('title', 200);
            $table->string('sku', 80)->unique();
            $table->unsignedBigInteger('category_id')->nullable()->default(null)->index();
            $table->string('short_description', 500)->nullable();
            $table->longText('full_description')->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->text('notes_tags')->nullable();
            $table->boolean('status')->default(false)->index(); // 0 = Draft, 1 = Active

            /* -------------------- Gemstone-specific fields ------------------ */
            $table->decimal('carat_weight', 8, 3)->nullable();
            $table->string('stone_type', 50)->nullable();
            $table->string('colour_grade', 100)->nullable();
            $table->string('clarity_grade', 20)->nullable();
            $table->string('cut_shape', 50)->nullable();
            $table->string('treatment', 50)->nullable();
            $table->string('certificate_number', 100)->nullable();

            /* ------------------------ Website visibility -------------------- */
            $table->boolean('website_enabled')->default(false)->index();
            $table->decimal('website_price', 12, 2)->nullable();
            $table->string('website_title', 200)->nullable();
            $table->longText('website_description')->nullable();
            $table->boolean('featured_product')->default(false)->index();
            $table->integer('website_sort_order')->nullable();
            $table->timestamp('website_enabled_at')->nullable();
            $table->timestamp('website_disabled_at')->nullable();

            /* ----------------------------- System --------------------------- */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /* --------------------------- Foreign keys ----------------------- */
            // RESTRICT: a category with linked products cannot be hard-deleted
            // (matches business rule from spec §2.3).
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();

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
        Schema::dropIfExists('products');
    }
};
