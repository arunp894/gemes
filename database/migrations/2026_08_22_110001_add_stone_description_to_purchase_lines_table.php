<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Line-level template counterpart to products.stone_description --
     * see the sibling migration. Stamped onto every Product a new row
     * creates under this line (PurchaseService::syncLines()), not
     * re-applied to already-created products on a later edit, same as
     * colour_grade/clarity_grade/cut_shape/treatment.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->longText('stone_description')->nullable()->after('treatment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn('stone_description');
        });
    }
};
