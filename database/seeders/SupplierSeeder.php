<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Seeds demo suppliers for Sukainagems.
 *
 * Idempotent: upserts on supplier_code.
 * supplier_code and invoice_prefix are set explicitly so the auto-generator
 * in Supplier::booted() is bypassed — these are known-stable codes.
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_code'  => 'SUP-0001',
                'name'           => 'Rajan Gems',
                'company_name'   => 'Rajan Gems & Minerals Pvt Ltd',
                'invoice_prefix' => 'RAJAN',
                'email'          => 'rajan@rajangems.com',
                'phone'          => '+91 98400 11111',
                'country'        => 'India',
                'state'          => 'Tamil Nadu',
                'city'           => 'Chennai',
                'opening_balance'=> 0.00,
                'credit_limit'   => 500000.00,
                'status'         => true,
            ],
            [
                'supplier_code'  => 'SUP-0002',
                'name'           => 'Sri Lanka Gems',
                'company_name'   => 'Ceylon Gem House',
                'invoice_prefix' => 'CEYLON',
                'email'          => 'info@ceylongemhouse.lk',
                'phone'          => '+94 77 123 4567',
                'country'        => 'Sri Lanka',
                'state'          => 'Western Province',
                'city'           => 'Colombo',
                'opening_balance'=> 0.00,
                'credit_limit'   => 300000.00,
                'status'         => true,
            ],
            [
                'supplier_code'  => 'SUP-0003',
                'name'           => 'Bangkok Minerals',
                'company_name'   => 'Bangkok Minerals Co.',
                'invoice_prefix' => 'BKKMN',
                'email'          => 'sales@bangkokmin.co.th',
                'phone'          => '+66 2 123 4567',
                'country'        => 'Thailand',
                'state'          => 'Bangkok',
                'city'           => 'Bangkok',
                'opening_balance'=> 0.00,
                'credit_limit'   => 200000.00,
                'status'         => true,
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(
                ['supplier_code' => $data['supplier_code']],
                $data
            );
        }
    }
}
