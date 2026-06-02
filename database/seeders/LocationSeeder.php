<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Seeds a default warehouse location for Sukainagems.
 *
 * Idempotent: upserts on location_code so re-runs are safe.
 * The main warehouse is flagged is_default so PurchaseService and
 * any future stock queries have a fallback without crashing.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::updateOrCreate(
            ['location_code' => 'LOC-0001'],
            [
                'name'        => 'Main Warehouse',
                'type'        => 'warehouse',
                'description' => 'Primary storage and dispatch location for Sukainagems.',
                'city'        => 'Rasipuram',
                'state'       => 'Tamil Nadu',
                'country'     => 'India',
                'is_default'  => true,
                'status'      => true,
            ]
        );

        Location::updateOrCreate(
            ['location_code' => 'LOC-0002'],
            [
                'name'        => 'Showroom',
                'type'        => 'showroom',
                'description' => 'Customer-facing display and sales area.',
                'city'        => 'Rasipuram',
                'state'       => 'Tamil Nadu',
                'country'     => 'India',
                'is_default'  => false,
                'status'      => true,
            ]
        );
    }
}
