<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * Seeds the default "Walk-in Customer" row. The sales terminal needs a
 * customer FK on every sale, and quick-counter sales don't always have
 * an identifiable buyer — this row is the catch-all.
 *
 * Idempotent: keyed by customer_code so re-running is safe.
 */
class WalkInCustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::withTrashed()->updateOrCreate(
            ['customer_code' => 'WALKIN'],
            [
                'name'          => 'Walk-in Customer',
                'company_name'  => null,
                'customer_type' => Customer::TYPE_WALKIN,
                'status'        => true,
                'notes'         => 'Default customer for counter / over-the-table sales.',
                'deleted_at'    => null,
            ]
        );
    }
}
