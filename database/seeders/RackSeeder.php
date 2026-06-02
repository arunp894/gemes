<?php

namespace Database\Seeders;

use App\Models\Rack;
use Illuminate\Database\Seeder;

/**
 * Seeds storage racks for the main warehouse.
 *
 * Rack codes follow: RACK-{SECTION}-{NUMBER}, e.g. RACK-A-01.
 * Idempotent: upserts on `code`.
 */
class RackSeeder extends Seeder
{
    public function run(): void
    {
        $racks = [
            // Section A — Gemstones
            ['code' => 'RACK-A-01', 'name' => 'Rack A-01', 'description' => 'Ruby & Sapphire — certified stones'],
            ['code' => 'RACK-A-02', 'name' => 'Rack A-02', 'description' => 'Emerald & Diamond — certified stones'],
            ['code' => 'RACK-A-03', 'name' => 'Rack A-03', 'description' => 'Other faceted gemstones'],

            // Section B — Rough Stones
            ['code' => 'RACK-B-01', 'name' => 'Rack B-01', 'description' => 'Ruby & Sapphire rough'],
            ['code' => 'RACK-B-02', 'name' => 'Rack B-02', 'description' => 'Mixed rough stones'],

            // Section C — Accessories
            ['code' => 'RACK-C-01', 'name' => 'Rack C-01', 'description' => 'Jewellery tools & equipment'],
            ['code' => 'RACK-C-02', 'name' => 'Rack C-02', 'description' => 'Packaging materials'],
        ];

        foreach ($racks as $data) {
            Rack::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'        => $data['name'],
                    'description' => $data['description'],
                    'status'      => true,
                ]
            );
        }
    }
}
