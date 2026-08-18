<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchases now CREATE products instead of referencing one that
     * already exists. purchase_lines becomes the template a new Product
     * is stamped from for every inventory row generated under it.
     *
     * product_id is kept for historical rows (pre-this-migration
     * purchases still point at their original shared product) but
     * becomes nullable — new lines leave it null. No backfill, same
     * convention as the lot_code migration.
     *
     * certificate_number is deliberately NOT added here: a certificate
     * number is unique per physical stone, so sharing one across every
     * product generated from a line would be wrong. It's set later, per
     * product, on the normal product edit screen.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->string('title', 200)->nullable()->after('product_id');
            $table->unsignedBigInteger('category_id')->nullable()->index()->after('title');

            $table->string('short_description', 500)->nullable()->after('category_id');
            $table->longText('full_description')->nullable()->after('short_description');
            $table->string('country_of_origin', 100)->nullable()->after('full_description');
            $table->text('notes_tags')->nullable()->after('country_of_origin');

            // Line-level default; each generated row's own carat_weight
            // (purchase_products.carat_weight) can still override it.
            $table->decimal('carat_weight', 8, 3)->nullable()->after('notes_tags');

            $table->string('stone_type', 50)->nullable()->after('carat_weight');
            $table->string('colour_grade', 100)->nullable()->after('stone_type');
            $table->string('clarity_grade', 20)->nullable()->after('colour_grade');
            $table->string('cut_shape', 50)->nullable()->after('clarity_grade');
            $table->string('treatment', 50)->nullable()->after('cut_shape');

            $table->foreign('category_id')
                ->references('id')->on('categories')->restrictOnDelete();
        });

        // product_id used to be required (every line pointed at an
        // existing catalogue product). New-style lines create their own
        // products instead, so it has to become optional. Raw statement,
        // same approach the project already uses for ENUM changes —
        // ->nullable()->change() needs doctrine/dbal, which isn't
        // installed here.
        DB::statement('ALTER TABLE purchase_lines MODIFY COLUMN product_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'title', 'category_id', 'short_description', 'full_description',
                'country_of_origin', 'notes_tags', 'carat_weight',
                'stone_type', 'colour_grade', 'clarity_grade', 'cut_shape', 'treatment',
            ]);
        });

        DB::statement('ALTER TABLE purchase_lines MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');
    }
};
