<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds the core gemstone category list for Sukainagems.
 *
 * Categories are a flat, single-level list — no parent/child nesting.
 * Every category here is a gemstone type (`is_gemstone` = true).
 *
 * Idempotent: upserts on `code`. Codes for stones carried over from the
 * previous list (Ruby, Sapphire, Emerald, Diamond) are unchanged so an
 * already-seeded database updates those rows in place instead of
 * duplicating them. This seeder does not delete categories that existed
 * before but are no longer listed here (e.g. Ruby Rough, Sapphire Rough,
 * Other Gemstones, Jewellery Tools, Packaging) — those rows are left
 * untouched in the database.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'GEMS-TNZ', 'name' => 'Tanzanite',      'is_gemstone' => true, 'display_order' => 1],
            ['code' => 'GEMS-PRB', 'name' => 'Paraiba',        'is_gemstone' => true, 'display_order' => 2],
            ['code' => 'GEMS-TRM', 'name' => 'Tourmaline',     'is_gemstone' => true, 'display_order' => 3],
            ['code' => 'GEMS-TSV', 'name' => 'Tsavorite',      'is_gemstone' => true, 'display_order' => 4],
            ['code' => 'GEMS-SPN', 'name' => 'Spinel',         'is_gemstone' => true, 'display_order' => 5],
            ['code' => 'GEMS-RBY', 'name' => 'Ruby',           'is_gemstone' => true, 'display_order' => 6],
            ['code' => 'GEMS-SPH', 'name' => 'Sapphire',       'is_gemstone' => true, 'display_order' => 7],
            ['code' => 'GEMS-SPS', 'name' => 'Spessartite',    'is_gemstone' => true, 'display_order' => 8],
            ['code' => 'GEMS-AQM', 'name' => 'Aquamarine',     'is_gemstone' => true, 'display_order' => 9],
            ['code' => 'GEMS-MRG', 'name' => 'Morganite',      'is_gemstone' => true, 'display_order' => 10],
            ['code' => 'GEMS-ZRC', 'name' => 'Zircon',         'is_gemstone' => true, 'display_order' => 11],
            ['code' => 'GEMS-GRD', 'name' => 'Grandidierite',  'is_gemstone' => true, 'display_order' => 12],
            ['code' => 'GEMS-PRD', 'name' => 'Peridot',        'is_gemstone' => true, 'display_order' => 13],
            ['code' => 'GEMS-SSP', 'name' => 'Star Sapphire',  'is_gemstone' => true, 'display_order' => 14],
            ['code' => 'GEMS-SRB', 'name' => 'Star Ruby',      'is_gemstone' => true, 'display_order' => 15],
            ['code' => 'GEMS-SPE', 'name' => 'Sphene',         'is_gemstone' => true, 'display_order' => 16],
            ['code' => 'GEMS-DSP', 'name' => 'Diaspore',       'is_gemstone' => true, 'display_order' => 17],
            ['code' => 'GEMS-GRN', 'name' => 'Garnet',         'is_gemstone' => true, 'display_order' => 18],
            ['code' => 'GEMS-HSN', 'name' => 'Hessonite',      'is_gemstone' => true, 'display_order' => 19],
            ['code' => 'GEMS-FLR', 'name' => 'Flourite',       'is_gemstone' => true, 'display_order' => 20],
            ['code' => 'GEMS-OPL', 'name' => 'Opal',           'is_gemstone' => true, 'display_order' => 21],
            ['code' => 'GEMS-SPL', 'name' => 'Sphalerite',     'is_gemstone' => true, 'display_order' => 22],
            ['code' => 'GEMS-EMR', 'name' => 'Emerald',        'is_gemstone' => true, 'display_order' => 23],
            ['code' => 'GEMS-DIA', 'name' => 'Diamond',        'is_gemstone' => true, 'display_order' => 24],
            ['code' => 'GEMS-KNZ', 'name' => 'Kunzite',        'is_gemstone' => true, 'display_order' => 25],
            ['code' => 'GEMS-MNS', 'name' => 'Moonstone',      'is_gemstone' => true, 'display_order' => 26],
            ['code' => 'GEMS-TPZ', 'name' => 'Topaz',          'is_gemstone' => true, 'display_order' => 27],
            ['code' => 'GEMS-CTR', 'name' => 'Citrine',        'is_gemstone' => true, 'display_order' => 28],
            ['code' => 'GEMS-AMT', 'name' => 'Amethyst',       'is_gemstone' => true, 'display_order' => 29],
            ['code' => 'GEMS-FOP', 'name' => 'Fire Opal',      'is_gemstone' => true, 'display_order' => 30],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name'          => $cat['name'],
                    'is_gemstone'   => $cat['is_gemstone'],
                    'display_order' => $cat['display_order'],
                    'status'        => true,
                ]
            );
        }
    }
}
