<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds packaging hierarchy to products. The model now answers two
     * questions every purchase line needs to know:
     *
     *   1. pack_type — is this product sold loose ('piece'),
     *      packaged once ('unit', e.g. a bag of 20),
     *      or packaged twice ('carton', e.g. 1 Carton = 2 Boxes = 40 pieces)?
     *
     *   2. how do the levels nest? — outer/inner names + their `contains` counts.
     *      For 'piece'   : both pairs are null.
     *      For 'unit'    : only inner_* is set    (1 unit  = N pieces).
     *      For 'carton'  : both outer_* and inner_* are set
     *                      (1 carton = outer_pack_contains inner-packs,
     *                       1 inner  = inner_pack_contains pieces).
     *
     * This is the simplest schema that still satisfies the spec's
     * cigarette example without forcing a join on every purchase save.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // pack_type drives auto-row generation in the purchase form.
            $table->enum('pack_type', ['piece', 'unit', 'carton'])
                ->default('piece')
                ->after('status');

            // 2-level packaging hierarchy.
            $table->string('outer_pack_name', 50)
                ->nullable()
                ->after('pack_type');

            $table->unsignedInteger('outer_pack_contains')
                ->nullable()
                ->after('outer_pack_name');

            $table->string('inner_pack_name', 50)
                ->nullable()
                ->after('outer_pack_contains');

            $table->unsignedInteger('inner_pack_contains')
                ->nullable()
                ->after('inner_pack_name');

            $table->index('pack_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['pack_type']);
            $table->dropColumn([
                'pack_type',
                'outer_pack_name',
                'outer_pack_contains',
                'inner_pack_name',
                'inner_pack_contains',
            ]);
        });
    }
};
