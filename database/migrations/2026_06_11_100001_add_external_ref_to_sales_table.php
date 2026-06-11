<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds external channel reference columns to the sales table.
 *
 * external_ref      – Platform's own order reference (eBay Sales Record #,
 *                     Catawiki lot #, etc.). Combined with channel_id forms
 *                     a unique key that blocks duplicate imports.
 * external_order_id – Higher-level platform order ID (eBay Order Number).
 *                     Multiple sale rows can share one external_order_id.
 * import_batch_id   – UUID stamped by the importer so all rows from one
 *                     file upload can be viewed or rolled back together.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('external_ref',      80)->nullable()->after('channel_id');
            $table->string('external_order_id', 80)->nullable()->after('external_ref');
            $table->string('import_batch_id',   36)->nullable()->after('external_order_id');

            // Prevent the same external reference being imported twice on the
            // same channel. NULL values are excluded from the unique index so
            // manually-entered sales (where both are null) never conflict.
            $table->unique(['channel_id', 'external_ref'], 'sales_channel_external_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_channel_external_ref_unique');
            $table->dropColumn(['external_ref', 'external_order_id', 'import_batch_id']);
        });
    }
};
