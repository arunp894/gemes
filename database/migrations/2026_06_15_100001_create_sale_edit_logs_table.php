<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sale_edit_logs — append-only audit trail of edits made to a sale.
 *
 * One row is written every time SaleService::update() successfully
 * persists a change, capturing WHO made the edit, WHEN, and a
 * field-level diff of what changed (header fields, line count, total).
 *
 * This complements the sales.updated_by column (which only ever holds
 * the *last* editor) by preserving the full history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_edit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id')->index();
            // Nullable so the log survives the editor's user being removed.
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Context label for the edit (e.g. 'updated').
            $table->string('action', 30)->default('updated');

            // Field-level diff: { field: { from: x, to: y }, ... }
            $table->json('changes')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sale_id')
                ->references('id')->on('sales')->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_edit_logs');
    }
};
