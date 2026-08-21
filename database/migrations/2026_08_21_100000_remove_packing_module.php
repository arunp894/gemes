<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverses the Aug 19 "Packing" pivot. Purchases go back to creating
     * their own sellable Products directly (see PurchaseService), so
     * every purchase_products row has a product_id again and nothing in
     * the ledger is ever raw/unpacked. Undoes, in dependency order:
     *   - 2026_08_19_100001_create_packings_table
     *   - 2026_08_19_100002_add_packing_id_to_purchase_products_table
     *   - 2026_08_19_100003_create_packing_sources_table
     *   - 2026_08_19_100004_add_pack_reasons_to_stock_movements_enum
     *   - 2026_08_19_100000_make_stock_movements_product_id_nullable
     *   - 2026_08_20_100000_add_website_fields_to_packing_sources_table
     *   - 2026_08_20_100001_make_stock_transfer_lines_product_id_nullable
     *
     * The NOT NULL restores below only succeed against data that has no
     * NULL product_id / pack_* rows left -- i.e. a fresh
     * `migrate:fresh --seed`, not a migrate over existing Packing-era
     * demo data.
     */
    public function up(): void
    {
        // packing_sources has FKs into both packings and purchase_products
        // -- drop it first so neither of those tables has a dependent
        // left when they're touched below.
        Schema::dropIfExists('packing_sources');

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->dropForeign(['packing_id']);
            $table->dropColumn('packing_id');
        });

        // Every row is raw-material intake again -- purchase_line_id is
        // mandatory once more. Raw ALTER: ->nullable()->change() needs
        // doctrine/dbal, which isn't installed (same approach the rest of
        // this project already uses for enum/nullable changes).
        DB::statement('ALTER TABLE purchase_products MODIFY COLUMN purchase_line_id BIGINT UNSIGNED NOT NULL');

        Schema::dropIfExists('packings');

        // reason enum: drop pack_in/pack_out/pack_cancel/pack_restore.
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN reason ENUM(
            'purchase',
            'purchase_cancel',
            'sale',
            'sale_return',
            'sale_cancel',
            'sale_edit_reverse',
            'transfer_out',
            'transfer_in',
            'transfer_cancel_out',
            'adjustment_in',
            'adjustment_out',
            'opening'
        ) NOT NULL");

        // Every movement names a real product again -- no more raw
        // (product_id null) rows can be written.
        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');

        // Same for transfer lines -- a transfer always moves a piece that
        // already has a product.
        DB::statement('ALTER TABLE stock_transfer_lines MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE stock_transfer_lines MODIFY COLUMN product_id BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE stock_movements MODIFY COLUMN product_id BIGINT UNSIGNED NULL');

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN reason ENUM(
            'purchase',
            'purchase_cancel',
            'sale',
            'sale_return',
            'sale_cancel',
            'sale_edit_reverse',
            'transfer_out',
            'transfer_in',
            'transfer_cancel_out',
            'adjustment_in',
            'adjustment_out',
            'opening',
            'pack_in',
            'pack_out',
            'pack_cancel',
            'pack_restore'
        ) NOT NULL");

        Schema::create('packings', function (Blueprint $table) {
            $table->id();
            $table->string('packing_number', 50)->unique();
            $table->date('packing_date')->index();
            $table->unsignedBigInteger('location_id')->index();

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft')
                ->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('location_id')
                ->references('id')->on('locations')->restrictOnDelete();
            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE purchase_products MODIFY COLUMN purchase_line_id BIGINT UNSIGNED NULL');

        Schema::table('purchase_products', function (Blueprint $table) {
            $table->unsignedBigInteger('packing_id')->nullable()->index()->after('purchase_line_id');

            $table->foreign('packing_id')
                ->references('id')->on('packings')->cascadeOnDelete();
        });

        Schema::create('packing_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('packing_id')->index();
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->unsignedInteger('qty_taken');
            $table->boolean('website_enabled')->default(false);
            $table->decimal('website_price', 12, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('packing_id')
                ->references('id')->on('packings')->cascadeOnDelete();
            $table->foreign('purchase_product_id')
                ->references('id')->on('purchase_products')->restrictOnDelete();
        });
    }
};
