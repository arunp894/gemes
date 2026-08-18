<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds payment_status to purchases, mirroring sales.payment_status.
     * Derived from purchase_payments (see PurchaseService::
     * recalculatePayments()) going forward, but backfilled here from the
     * existing paid_amount / grand_total figures so purchases created
     * before this column existed don't all render as "Unpaid".
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])
                ->default('unpaid')
                ->after('due_amount')
                ->index();
        });

        DB::table('purchases')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $paid  = (float) $row->paid_amount;
                $grand = (float) $row->grand_total;

                $status = 'unpaid';
                if ($paid > 0.0001) {
                    $status = ($grand > 0 && $paid + 0.0001 >= $grand) ? 'paid' : 'partial';
                }

                DB::table('purchases')->where('id', $row->id)->update(['payment_status' => $status]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
