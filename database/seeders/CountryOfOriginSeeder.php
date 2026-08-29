<?php

namespace Database\Seeders;

use App\Models\CountryOfOrigin;
use Illuminate\Database\Seeder;

/**
 * Idempotent: upserts on `name`. This seeder does not delete countries
 * that existed before but are no longer listed here — those rows are
 * left untouched in the database.
 */
class CountryOfOriginSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'Tanzania',
            'Mozambique',
            'Zambia',
            'Nigeria',
            'Brazil',
            'Ethiopia',
            'Ceylon',
            'Africa',
            'Madagascar',
            'India',
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
