<?php

namespace Database\Seeders;

use App\Models\CountryOfOrigin;
use Illuminate\Database\Seeder;

/**
 * Common gemstone-origin countries, weighted toward Sukaina Gems'
 * specialties (Paraiba Tourmaline: Mozambique/Brazil/Nigeria; Tanzanite:
 * Tanzania only). Idempotent — safe to re-run.
 */
class CountryOfOriginSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'Mozambique',
            'Tanzania',
            'Brazil',
            'Nigeria',
            'Madagascar',
            'Sri Lanka',
            'Myanmar (Burma)',
            'Zambia',
            'Kenya',
            'Thailand',
            'Colombia',
            'Afghanistan',
            'India',
        ];

        foreach ($countries as $i => $name) {
            CountryOfOrigin::updateOrCreate(
                ['name' => $name],
                ['status' => true, 'display_order' => $i],
            );
        }
    }
}
