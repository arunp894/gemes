<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock transfer document (header).
     *
     * Numbering: TRF-YYYYMM-#### (global per-month sequence, 4-digit pad).
     *
     * Lifecycle:
     *   - draft       : being built, no stock impact
     *   - in_transit  : posted — OUT movements created at source.
     *                   Pieces are "in flight"; reports treat them as
     *                   "in_transit" — gone from source, not yet at
     *                   destination.
     *   - received    : destination confirmed — IN movements created.
     *                   Pieces are now on-hand at to_location.
     *   - cancelled   : either pre-posting (no stock impact) or after
     *                   in-transit (compensating IN at source to restore).
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->date('transfer_date')->index();

            $table->unsignedBigInteger('from_location_id')->index();
            $table->unsignedBigInteger('to_location_id')->index();

            $table->enum('status', ['draft', 'in_transit', 'received', 'cancelled'])
                ->default('draft')
                ->index();

            // Timestamps for the actual physical events. `created_at` is
            // when the document was first built; these tell us when
            // each state transition happened.
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_location_id')
                ->references('id')->on('locations')->restrictOnDelete();

            $table->foreign('to_location_id')
                ->references('id')->on('locations')->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
