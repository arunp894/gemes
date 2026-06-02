<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Seeds the core gemstone category hierarchy for Sukainagems.
 *
 * Structure (two levels max):
 *   Gemstones (is_gemstone=true)
 *     ├── Ruby
 *     ├── Sapphire
 *     ├── Emerald
 *     ├── Diamond
 *     └── Other Gemstones
 *   Rough Stones (is_gemstone=true)
 *     ├── Ruby Rough
 *     └── Sapphire Rough
 *   Accessories (is_gemstone=false)
 *     ├── Jewellery Tools
 *     └── Packaging
 *
 * Idempotent: upserts on `code`.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Top-level categories ──────────────────────────────────
        $gemstones = Category::updateOrCreate(
            ['code' => 'GEMS'],
            [
                'name'         => 'Gemstones',
                'is_gemstone'  => true,
                'parent_id'    => null,
                'display_order'=> 1,
                'status'       => true,
            ]
        );

        $rough = Category::updateOrCreate(
            ['code' => 'ROUGH'],
            [
                'name'         => 'Rough Stones',
                'is_gemstone'  => true,
                'parent_id'    => null,
                'display_order'=> 2,
                'status'       => true,
            ]
        );

        $accessories = Category::updateOrCreate(
            ['code' => 'ACCS'],
            [
                'name'         => 'Accessories',
                'is_gemstone'  => false,
                'parent_id'    => null,
                'display_order'=> 3,
                'status'       => true,
            ]
        );

        // ── Gemstones sub-categories ──────────────────────────────
        $subGems = [
            ['code' => 'GEMS-RBY',  'name' => 'Ruby',             'display_order' => 1],
            ['code' => 'GEMS-SPH',  'name' => 'Sapphire',         'display_order' => 2],
            ['code' => 'GEMS-EMR',  'name' => 'Emerald',          'display_order' => 3],
            ['code' => 'GEMS-DIA',  'name' => 'Diamond',          'display_order' => 4],
            ['code' => 'GEMS-OTH',  'name' => 'Other Gemstones',  'display_order' => 5],
        ];

        foreach ($subGems as $sub) {
            Category::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'name'          => $sub['name'],
                    'parent_id'     => $gemstones->id,
                    'is_gemstone'   => false, // forced false by CategoryController rule
                    'display_order' => $sub['display_order'],
                    'status'        => true,
                ]
            );
        }

        // ── Rough Stones sub-categories ───────────────────────────
        $subRough = [
            ['code' => 'ROUGH-RBY', 'name' => 'Ruby Rough',     'display_order' => 1],
            ['code' => 'ROUGH-SPH', 'name' => 'Sapphire Rough', 'display_order' => 2],
        ];

        foreach ($subRough as $sub) {
            Category::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'name'          => $sub['name'],
                    'parent_id'     => $rough->id,
                    'is_gemstone'   => false,
                    'display_order' => $sub['display_order'],
                    'status'        => true,
                ]
            );
        }

        // ── Accessories sub-categories ────────────────────────────
        $subAccs = [
            ['code' => 'ACCS-TOOLS', 'name' => 'Jewellery Tools', 'display_order' => 1],
            ['code' => 'ACCS-PACK',  'name' => 'Packaging',       'display_order' => 2],
        ];

        foreach ($subAccs as $sub) {
            Category::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'name'          => $sub['name'],
                    'parent_id'     => $accessories->id,
                    'is_gemstone'   => false,
                    'display_order' => $sub['display_order'],
                    'status'        => true,
                ]
            );
        }
    }
}
