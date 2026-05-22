<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the `invoice_prefix` column to suppliers. The prefix is used when
     * generating purchase invoice numbers in the format:
     *   {invoice_prefix}-{YYYYMM}-{0001}
     * e.g.  ACME-202605-0001
     *
     * The column is nullable so existing suppliers don't fail validation,
     * but a backfill query immediately seeds it from supplier_code so every
     * row has a usable value. The Supplier model also auto-generates a
     * prefix in its creating() hook for any future row with a blank prefix.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('invoice_prefix', 10)
                ->nullable()
                ->after('company_name');

            $table->index('invoice_prefix');
        });

        // Backfill: derive from supplier_code (e.g. "SUP-0001" -> "SUP0001").
        // Uppercased, dashes stripped, capped at 10 characters.
        DB::statement(
            "UPDATE suppliers
                SET invoice_prefix = UPPER(SUBSTRING(REPLACE(supplier_code, '-', ''), 1, 10))
              WHERE invoice_prefix IS NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['invoice_prefix']);
            $table->dropColumn('invoice_prefix');
        });
    }
};
