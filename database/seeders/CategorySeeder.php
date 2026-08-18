<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds the core gemstone category list for Sukainagems.
 *
 * Categories are a flat, single-level list — no parent/child nesting.
 * `is_gemstone` is set directly on each row:
 *
 *   Ruby              (is_gemstone=true)
 *   Sapphire          (is_gemstone=true)
 *   Emerald           (is_gemstone=true)
 *   Diamond           (is_gemstone=true)
 *   Other Gemstones   (is_gemstone=true)
 *   Ruby Rough        (is_gemstone=true)
 *   Sapphire Rough    (is_gemstone=true)
 *   Jewellery Tools   (is_gemstone=false)
 *   Packaging         (is_gemstone=false)
 *
 * Idempotent: upserts on `code`.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'GEMS-RBY',   'name' => 'Ruby',            'is_gemstone' => true,  'display_order' => 1],
            ['code' => 'GEMS-SPH',   'name' => 'Sapphire',        'is_gemstone' => true,  'display_order' => 2],
            ['code' => 'GEMS-EMR',   'name' => 'Emerald',         'is_gemstone' => true,  'display_order' => 3],
            ['code' => 'GEMS-DIA',   'name' => 'Diamond',         'is_gemstone' => true,  'display_order' => 4],
            ['code' => 'GEMS-OTH',   'name' => 'Other Gemstones', 'is_gemstone' => true,  'display_order' => 5],
            ['code' => 'ROUGH-RBY',  'name' => 'Ruby Rough',      'is_gemstone' => true,  'display_order' => 6],
            ['code' => 'ROUGH-SPH',  'name' => 'Sapphire Rough',  'is_gemstone' => true,  'display_order' => 7],
            ['code' => 'ACCS-TOOLS', 'name' => 'Jewellery Tools', 'is_gemstone' => false, 'display_order' => 8],
            ['code' => 'ACCS-PACK',  'name' => 'Packaging',       'is_gemstone' => false, 'display_order' => 9],
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
