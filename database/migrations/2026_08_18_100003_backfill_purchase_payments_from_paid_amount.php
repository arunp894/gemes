<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data migration only — no schema change.
     *
     * Before this feature, paid_amount was a single number typed
     * directly onto the purchase. Now it's derived as
     * SUM(purchase_payments.amount) (see PurchaseService::
     * recalculatePayments()). Without this backfill, the next time any
     * pre-existing purchase with paid_amount > 0 is saved, its paid
     * amount would silently reset to 0 because no payment rows exist
     * for it yet. This inserts one payment row per such purchase so the
     * derived total reproduces the value already on record.
     */
    public function up(): void
    {
        DB::table('purchases')
            ->where('paid_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                $now     = now();
                $inserts = [];

                foreach ($rows as $row) {
                    $inserts[] = [
                        'purchase_id'      => $row->id,
                        'payment_date'     => $row->purchase_date,
                        'amount'           => $row->paid_amount,
                        'payment_method'   => 'other',
                        'reference_number' => null,
                        'notes'            => 'Migrated from legacy paid_amount field.',
                        'created_by'       => $row->created_by,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                if ($inserts) {
                    DB::table('purchase_payments')->insert($inserts);
                }
            });
    }

    public function down(): void
    {
        DB::table('purchase_payments')
            ->where('notes', 'Migrated from legacy paid_amount field.')
            ->delete();
    }
};
