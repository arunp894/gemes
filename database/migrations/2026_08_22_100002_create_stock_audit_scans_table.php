<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw log of every scan event during a stock audit, including
     * duplicates and scans that matched nothing in the snapshot. Kept
     * separate from stock_audit_items (the matched/unmatched state) so
     * the full "who scanned what, when" trail survives even an undo,
     * and so the scan screen can show a running feed.
     */
    public function up(): void
    {
        Schema::create('stock_audit_scans', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_audit_id')->index();
            $table->unsignedBigInteger('stock_audit_item_id')->nullable()->index();

            // Whatever string was actually typed/scanned — may be a
            // lot_code, a dock barcode, or an operator typo. Not unique;
            // the same value can legitimately be scanned many times
            // (duplicates) across an audit.
            $table->string('scanned_value', 100)->index();

            $table->enum('result', ['matched', 'duplicate', 'unexpected'])->index();

            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->timestamp('scanned_at')->index();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stock_audit_id')
                ->references('id')->on('stock_audits')->cascadeOnDelete();

            $table->foreign('stock_audit_item_id')
                ->references('id')->on('stock_audit_items')->nullOnDelete();

            $table->foreign('scanned_by')
                ->references('id')->on('users')->nullOnDelete();

            // Feed queries: "latest N scans for this audit".
            $table->index(['stock_audit_id', 'scanned_at'], 'idx_audit_scan_feed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_audit_scans');
    }
};
