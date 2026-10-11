<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for purchase edits — who edited, when, and a field-level diff of
 * the header and of every changed row — shown in the purchase page's
 * "Edit History" popup. Mirrors sale_edit_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_edit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_id')->index();
            // Nullable so the log survives the editor's user being removed.
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('action', 30)->default('updated');

            // { header: {field:{from,to}}, rows: [{purchase_product_id, lot, line, change, fields}],
            //   stock: [{lot, line, from_qty, to_qty}], ledger_mismatches: [{...}] }
            $table->json('changes')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_edit_logs');
    }
};
