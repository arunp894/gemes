<?php

namespace Database\Seeders;

use App\Models\CountryOfOrigin;
use Illuminate\Database\Seeder;

class CountryOfOriginSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'Himalayan',
            'Kenya',
            'Nigeria',
            'Mexico',
            'Switzerland',
            'Zambia',
            'Cambodia',
            'Ceylon – Sri Lanka',
            'Pakistan',
            'Tanzania',
            'Burma (Myanmar)',
            'Siberia',
            'Spain',
            'Tanzania – Merelani Hills',
            'Thailand',
            'Turkey',
            'United States',
            'Sri Lanka',
            'Nepal',
            'Namibia',
            'Ireland',
            'Indonesia',
            'Germany',
            'Czech Republic',
            'Congo',
            'Cambodia',
            'Colombia',
            'Bolivia',
            'Australia',
            'Africa',
            'Afghanistan',
            'Canada',
            'Russia',
            'Mexico',
            'India',
            'Vietnam',
            'Madagascar',
            'Ethiopia',
            'Brazil',
            'Mozambique',
            'Tanzania – United Republic of',
        ];

        foreach ($countries as $i => $name) {
            CountryOfOrigin::updateOrCreate(
                ['name' => $name],
                [
                    'status' => true,
                    'display_order' => $i + 1,
                ],
            );
        }
    }
}
