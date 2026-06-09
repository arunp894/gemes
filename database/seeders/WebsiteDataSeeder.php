<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * WebsiteDataSeeder
 *
 * Seeds everything the public storefront needs to display real content:
 *   1. Extended categories  — Paraiba, Tanzanite, Zircon, Peridot, Tourmaline Sets
 *   2. 12 demo products     — website_enabled, with prices, carat, origin, featured flags
 *   3. 3 banners            — home hero, category promo, product page accent
 *
 * Idempotent: uses updateOrCreate / firstOrCreate on stable unique keys.
 * Safe to run on an existing database — will not duplicate data.
 */
class WebsiteDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Make sure base seeders have run first (in case this is run standalone)
        ]);

        $this->seedCategories();
        $this->seedProducts();
        $this->seedBanners();
    }

    // ─────────────────────────────────────────────────────────────────
    //  1. CATEGORIES
    //  Adds Sukaina Gems specialist categories alongside existing ones.
    // ─────────────────────────────────────────────────────────────────
    private function seedCategories(): void
    {
        // ── Top-level gemstone categories ────────────────────────────
        $paraiba = Category::updateOrCreate(
            ['code' => 'PARAIBA'],
            [
                'name'          => 'Paraiba Tourmaline',
                'is_gemstone'   => true,
                'parent_id'     => null,
                'display_order' => 10,
                'status'        => true,
            ]
        );

        $tanzanite = Category::updateOrCreate(
            ['code' => 'TANZANITE'],
            [
                'name'          => 'Tanzanite',
                'is_gemstone'   => true,
                'parent_id'     => null,
                'display_order' => 11,
                'status'        => true,
            ]
        );

        $zircon = Category::updateOrCreate(
            ['code' => 'ZIRCON'],
            [
                'name'          => 'Natural Zircon',
                'is_gemstone'   => true,
                'parent_id'     => null,
                'display_order' => 12,
                'status'        => true,
            ]
        );

        $peridot = Category::updateOrCreate(
            ['code' => 'PERIDOT'],
            [
                'name'          => 'Peridot',
                'is_gemstone'   => true,
                'parent_id'     => null,
                'display_order' => 13,
                'status'        => true,
            ]
        );

        $sets = Category::updateOrCreate(
            ['code' => 'GEM-SETS'],
            [
                'name'          => 'Gem Sets',
                'is_gemstone'   => true,
                'parent_id'     => null,
                'display_order' => 14,
                'status'        => true,
            ]
        );

        // ── Sub-categories ────────────────────────────────────────────
        $subs = [
            // Paraiba
            ['code' => 'PARAIBA-BRZ', 'name' => 'Brazil Paraiba',      'parent' => $paraiba->id,   'order' => 1],
            ['code' => 'PARAIBA-MOZ', 'name' => 'Mozambique Paraiba',   'parent' => $paraiba->id,   'order' => 2],
            ['code' => 'PARAIBA-NIG', 'name' => 'Nigerian Paraiba',     'parent' => $paraiba->id,   'order' => 3],
            // Tanzanite
            ['code' => 'TANZ-AA',    'name' => 'AA Grade Tanzanite',    'parent' => $tanzanite->id, 'order' => 1],
            ['code' => 'TANZ-AAA',   'name' => 'AAA Grade Tanzanite',   'parent' => $tanzanite->id, 'order' => 2],
            // Zircon
            ['code' => 'ZIRC-BLUE',  'name' => 'Blue Zircon',          'parent' => $zircon->id,    'order' => 1],
            ['code' => 'ZIRC-GRN',   'name' => 'Green Zircon',         'parent' => $zircon->id,    'order' => 2],
            // Peridot
            ['code' => 'PERI-PAK',   'name' => 'Pakistan Peridot',     'parent' => $peridot->id,   'order' => 1],
            // Sets
            ['code' => 'SETS-PAIR',  'name' => 'Matched Pairs',        'parent' => $sets->id,      'order' => 1],
            ['code' => 'SETS-TRIO',  'name' => 'Three-Stone Sets',     'parent' => $sets->id,      'order' => 2],
        ];

        foreach ($subs as $s) {
            Category::updateOrCreate(
                ['code' => $s['code']],
                [
                    'name'          => $s['name'],
                    'parent_id'     => $s['parent'],
                    'is_gemstone'   => false,
                    'display_order' => $s['order'],
                    'status'        => true,
                ]
            );
        }

        $this->command->info('✓ Categories seeded');
    }

    // ─────────────────────────────────────────────────────────────────
    //  2. PRODUCTS
    //  12 real-world Sukaina Gems style products, website_enabled = true
    // ─────────────────────────────────────────────────────────────────
    private function seedProducts(): void
    {
        // Resolve category IDs by code (safe — categories seeded above)
        $catMap = Category::whereIn('code', [
            'PARAIBA-BRZ', 'PARAIBA-MOZ', 'TANZ-AAA', 'TANZ-AA',
            'ZIRC-BLUE', 'PERI-PAK', 'SETS-TRIO',
        ])->pluck('id', 'code');

        $products = [
            // ── Paraiba Tourmaline ─────────────────────────────────
            [
                'sku'               => 'SGP-0001',
                'title'             => '0.89 CT Greenish Blue Brazil Paraiba Top Quality',
                'category_id'       => $catMap['PARAIBA-BRZ'] ?? null,
                'short_description' => 'A breathtaking Paraiba tourmaline with signature neon glow, sourced directly from the Batalha mine in Brazil.',
                'full_description'  => "This extraordinary Paraiba tourmaline hails from the legendary Batalha mine in Paraíba, Brazil. It exhibits a saturated greenish-blue tone with exceptional clarity and brilliance. Completely unheated and unenhanced, it represents Paraiba tourmaline in its most pure, natural state.\n\nParaiba tourmalines carry copper and manganese within their crystal structure, giving rise to an electrifying neon glow that no other gemstone can replicate.",
                'country_of_origin' => 'Brazil',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 0.890,
                'colour_grade'      => 'Greenish Blue',
                'clarity_grade'     => 'VS2',
                'cut_shape'         => 'Oval',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-6221904537',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 542500.00,
                'website_title'     => '0.89 CT Greenish Blue Brazil Paraiba',
                'website_description'=> 'Neon greenish-blue Paraiba tourmaline from the Batalha mine, Brazil. GIA certified, unheated.',
                'featured_product'  => true,
                'website_sort_order'=> 1,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGP-0002',
                'title'             => '1.45 CT Greenish Blue Brazil Paraiba Top Quality',
                'category_id'       => $catMap['PARAIBA-BRZ'] ?? null,
                'short_description' => 'Rare pear-shaped Paraiba tourmaline with the famous copper-bearing neon saturation.',
                'full_description'  => "A larger pear-shaped Paraiba with intense neon saturation. This stone showcases the classic copper-manganese fluorescence that makes Brazilian Paraibas among the most sought-after gems in the world. GIA certified with a full audit trail.",
                'country_of_origin' => 'Brazil',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 1.450,
                'colour_grade'      => 'Intense Blue-Green',
                'clarity_grade'     => 'VS1',
                'cut_shape'         => 'Pear',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-6884231100',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 1208000.00,
                'website_title'     => '1.45 CT Pear Paraiba — Intense Blue-Green',
                'website_description'=> 'Intense blue-green pear-shaped Paraiba tourmaline. GIA certified, unheated, conflict-free.',
                'featured_product'  => true,
                'website_sort_order'=> 2,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGP-0003',
                'title'             => '1.69 CT Bluish Green Brazil Paraiba',
                'category_id'       => $catMap['PARAIBA-BRZ'] ?? null,
                'short_description' => 'A strikingly large Brazilian Paraiba with an alluring bluish-green hue.',
                'full_description'  => "Natural slightly-included bluish-green Paraiba tourmaline from Brazil. The large carat weight at this saturation level is exceptionally rare. Natural inclusions present are characteristic of genuine Paraiba material and do not detract from the stone's luminosity.",
                'country_of_origin' => 'Brazil',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 1.690,
                'colour_grade'      => 'Bluish Green',
                'clarity_grade'     => 'SI1',
                'cut_shape'         => 'Oval',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-5191746220',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 1665000.00,
                'website_title'     => '1.69 CT Bluish Green Brazil Paraiba',
                'website_description'=> 'Rare large Paraiba tourmaline with vivid bluish-green colour. GIA certified.',
                'featured_product'  => true,
                'website_sort_order'=> 3,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGP-0004',
                'title'             => '1.73 CT Neon Blue Natural Paraiba Top Quality',
                'category_id'       => $catMap['PARAIBA-BRZ'] ?? null,
                'short_description' => 'A vivid neon blue Paraiba displaying the signature copper-induced fluorescence.',
                'full_description'  => "A vivid neon blue Paraiba tourmaline in a well-cut cushion shape. The copper-induced glow is phenomenal — this stone appears to emit its own light. GIA certified as natural and unheated.",
                'country_of_origin' => 'Brazil',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 1.730,
                'colour_grade'      => 'Neon Blue',
                'clarity_grade'     => 'VS2',
                'cut_shape'         => 'Cushion',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-1222095480',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 166600.00,
                'website_title'     => '1.73 CT Neon Blue Paraiba',
                'website_description'=> 'Cushion-cut neon blue Paraiba tourmaline from Brazil. GIA certified, unheated.',
                'featured_product'  => false,
                'website_sort_order'=> 4,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGP-0005',
                'title'             => '10.08 CT Neon Blue Natural Paraiba — Collector Piece',
                'category_id'       => $catMap['PARAIBA-BRZ'] ?? null,
                'short_description' => 'An extraordinary double-digit carat Paraiba — a once-in-a-decade find.',
                'full_description'  => "This magnificent 10.08 carat Paraiba tourmaline is among the finest specimens to come to market. Double-digit carat Paraibas with this level of neon saturation are exceptionally rare. Perfect for serious collectors or as a centrepiece for a high-jewellery commission. GIA certified.",
                'country_of_origin' => 'Brazil',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 10.080,
                'colour_grade'      => 'Neon Blue',
                'clarity_grade'     => 'VS1',
                'cut_shape'         => 'Oval',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-2225006790',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 5290500.00,
                'website_title'     => '10.08 CT Neon Blue Paraiba — Collector Piece',
                'website_description'=> 'Extraordinary 10+ carat neon blue Paraiba tourmaline from Brazil. Investment-grade. GIA certified.',
                'featured_product'  => true,
                'website_sort_order'=> 5,
                'pack_type'         => 'piece',
            ],
            // ── Tanzanite ──────────────────────────────────────────
            [
                'sku'               => 'SGT-0001',
                'title'             => '10.29 CT Natural No Heat Tanzanite Set — 3 Pieces',
                'category_id'       => $catMap['SETS-TRIO'] ?? null,
                'short_description' => 'A matched suite of three unheated tanzanites with exceptional trichroic colour shift.',
                'full_description'  => "Three perfectly matched cushion-cut tanzanites totalling 10.29 carats. All three stones are completely unheated, displaying tanzanite's famous trichroism — vivid violet-blue in one direction, burgundy-red in another. Ideal for a three-stone ring or pendant suite.",
                'country_of_origin' => 'Tanzania',
                'stone_type'        => 'Tanzanite',
                'carat_weight'      => 10.290,
                'colour_grade'      => 'Violet Blue',
                'clarity_grade'     => 'VS1',
                'cut_shape'         => 'Cushion',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-5221904111',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 216700.00,
                'website_title'     => '10.29 CT Natural Unheated Tanzanite Set (3 Stones)',
                'website_description'=> 'Matched set of 3 unheated cushion tanzanites with vivid violet-blue trichroism. GIA certified.',
                'featured_product'  => true,
                'website_sort_order'=> 6,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGT-0002',
                'title'             => '4.85 CT AAA Tanzanite — Deep Violet Blue',
                'category_id'       => $catMap['TANZ-AAA'] ?? null,
                'short_description' => 'A richly saturated AAA grade tanzanite with pure violet-blue body colour.',
                'full_description'  => "Exceptional AAA grade tanzanite displaying the deep blue-violet hue collectors prize. Heat treated in accordance with standard industry practice to optimise colour. Certified by a leading gem laboratory.",
                'country_of_origin' => 'Tanzania',
                'stone_type'        => 'Tanzanite',
                'carat_weight'      => 4.850,
                'colour_grade'      => 'Deep Violet Blue',
                'clarity_grade'     => 'VVS2',
                'cut_shape'         => 'Cushion',
                'treatment'         => 'Heat',
                'certificate_number'=> 'AGL-TZ48271',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 72750.00,
                'website_title'     => '4.85 CT AAA Tanzanite — Deep Violet Blue',
                'website_description'=> 'AAA grade deep violet-blue tanzanite from Merelani Hills, Tanzania. AGL certified.',
                'featured_product'  => false,
                'website_sort_order'=> 7,
                'pack_type'         => 'piece',
            ],
            // ── Zircon ────────────────────────────────────────────
            [
                'sku'               => 'SGZ-0001',
                'title'             => '10.12 CT Premium Blue Cambodia Natural Zircon',
                'category_id'       => $catMap['ZIRC-BLUE'] ?? null,
                'short_description' => 'A brilliantly cut Cambodian blue zircon — a natural alternative to sapphire with exceptional fire.',
                'full_description'  => "Cambodian blue zircon is prized for its extraordinary brilliance and fire, often surpassing that of blue sapphire. This 10.12 carat specimen has been heat-treated in Cambodia per standard practice to achieve its vivid sky-blue colour. Pear brilliant cut, eye clean.",
                'country_of_origin' => 'Cambodia',
                'stone_type'        => 'Zircon',
                'carat_weight'      => 10.120,
                'colour_grade'      => 'Sky Blue',
                'clarity_grade'     => 'Eye Clean',
                'cut_shape'         => 'Pear',
                'treatment'         => 'Heat',
                'certificate_number'=> 'AGL-CS89942',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 116600.00,
                'website_title'     => '10.12 CT Blue Cambodian Zircon — Premium Quality',
                'website_description'=> 'Sky-blue natural zircon from Cambodia. Brilliant pear cut with exceptional fire. AGL certified.',
                'featured_product'  => true,
                'website_sort_order'=> 8,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGZ-0002',
                'title'             => '5.60 CT Blue Green Natural Zircon — Cambodia',
                'category_id'       => $catMap['ZIRC-BLUE'] ?? null,
                'short_description' => 'A vivid blue-green zircon with intense dispersion and natural brilliance.',
                'full_description'  => "Cambodian blue-green zircon exhibiting vivid dispersion and natural luster. Zircon has the highest refractive index of any natural mineral, making it exceptionally brilliant. This stone is a perfect choice for a distinctive collector piece.",
                'country_of_origin' => 'Cambodia',
                'stone_type'        => 'Zircon',
                'carat_weight'      => 5.600,
                'colour_grade'      => 'Blue Green',
                'clarity_grade'     => 'VS2',
                'cut_shape'         => 'Oval',
                'treatment'         => 'Heat',
                'certificate_number'=> null,
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 28000.00,
                'website_title'     => '5.60 CT Blue-Green Cambodia Zircon',
                'website_description'=> 'Vivid blue-green natural zircon from Cambodia. Exceptional dispersion and brilliance.',
                'featured_product'  => false,
                'website_sort_order'=> 9,
                'pack_type'         => 'piece',
            ],
            // ── Peridot ───────────────────────────────────────────
            [
                'sku'               => 'SGR-0001',
                'title'             => '10.60 CT Yellowish Green Fine Quality Paraiba-Type',
                'category_id'       => $catMap['PERI-PAK'] ?? null,
                'short_description' => 'A rare yellowish-green stone with vivid saturation, showcasing the full spectrum of fine colour.',
                'full_description'  => "A striking large peridot from the Supat Valley in Pakistan — the source of the world's finest peridot. This 10.60 carat stone exhibits a pure yellowish-green colour with excellent saturation and eye-clean clarity.",
                'country_of_origin' => 'Pakistan',
                'stone_type'        => 'Peridot',
                'carat_weight'      => 10.600,
                'colour_grade'      => 'Yellowish Green',
                'clarity_grade'     => 'Eye Clean',
                'cut_shape'         => 'Oval',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-3311094720',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 500000.00,
                'website_title'     => '10.60 CT Fine Yellowish Green Peridot — Pakistan',
                'website_description'=> 'Vivid yellowish-green peridot from Supat Valley, Pakistan. GIA certified, unheated.',
                'featured_product'  => true,
                'website_sort_order'=> 10,
                'pack_type'         => 'piece',
            ],
            // ── Mozambique Paraiba ────────────────────────────────
            [
                'sku'               => 'SGPM-0001',
                'title'             => '2.34 CT Neon Blue Mozambique Paraiba Tourmaline',
                'category_id'       => $catMap['PARAIBA-MOZ'] ?? null,
                'short_description' => 'Vivid neon blue Paraiba from Mozambique with GIA copper confirmation.',
                'full_description'  => "African Paraiba tourmalines from Mozambique have emerged as genuine rivals to their Brazilian counterparts. This 2.34 carat stone shows extraordinary neon saturation with GIA copper/manganese confirmation. A superb value compared to equivalent Brazilian material.",
                'country_of_origin' => 'Mozambique',
                'stone_type'        => 'Paraiba Tourmaline',
                'carat_weight'      => 2.340,
                'colour_grade'      => 'Neon Blue',
                'clarity_grade'     => 'VS2',
                'cut_shape'         => 'Cushion',
                'treatment'         => 'None',
                'certificate_number'=> 'GIA-6112847330',
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 374400.00,
                'website_title'     => '2.34 CT Neon Blue Mozambique Paraiba',
                'website_description'=> 'GIA-confirmed copper-bearing Paraiba tourmaline from Mozambique. Unheated, neon blue.',
                'featured_product'  => true,
                'website_sort_order'=> 11,
                'pack_type'         => 'piece',
            ],
            [
                'sku'               => 'SGT-0003',
                'title'             => '3.12 CT Blue Violet Tanzanite — Excellent Cut',
                'category_id'       => $catMap['TANZ-AA'] ?? null,
                'short_description' => 'Well-cut AA grade tanzanite with strong violet-blue body colour, ideal for setting.',
                'full_description'  => "A beautifully proportioned oval tanzanite with a strong blue-violet body colour. The excellent cut maximises the trichroism — rotating the stone reveals blue, violet, and burgundy flashes. Heat treated to standard industry practice to develop full colour.",
                'country_of_origin' => 'Tanzania',
                'stone_type'        => 'Tanzanite',
                'carat_weight'      => 3.120,
                'colour_grade'      => 'Blue Violet',
                'clarity_grade'     => 'VS1',
                'cut_shape'         => 'Oval',
                'treatment'         => 'Heat',
                'certificate_number'=> null,
                'status'            => true,
                'website_enabled'   => true,
                'website_price'     => 37440.00,
                'website_title'     => '3.12 CT Blue Violet Tanzanite',
                'website_description'=> 'AA grade oval tanzanite with excellent cut and vivid blue-violet colour.',
                'featured_product'  => false,
                'website_sort_order'=> 12,
                'pack_type'         => 'piece',
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, [
                    'website_enabled_at' => now(),
                ])
            );
        }

        $this->command->info('✓ Products seeded (' . count($products) . ')');
    }

    // ─────────────────────────────────────────────────────────────────
    //  3. BANNERS
    // ─────────────────────────────────────────────────────────────────
    private function seedBanners(): void
    {
        $banners = [
            [
                'title'      => 'Rare Gems, Crafted Into Legacy',
                'subtitle'   => 'Specialists in Paraiba Tourmaline & Tanzanite — 5+ years of precious gems.',
                'link_url'   => '/store/collections',
                'link_text'  => 'Shop All Gems',
                'position'   => 'home',
                'sort_order' => 1,
                'status'     => true,
                'starts_at'  => null,
                'ends_at'    => null,
            ],
            [
                'title'      => 'GIA Certified. Ethically Sourced.',
                'subtitle'   => 'Every gem backed by independent lab certification and a full chain of custody.',
                'link_url'   => '/store/collections?category=paraiba',
                'link_text'  => 'View Paraiba Collection',
                'position'   => 'promo',
                'sort_order' => 1,
                'status'     => true,
                'starts_at'  => null,
                'ends_at'    => null,
            ],
            [
                'title'      => 'Free Insured Shipping Worldwide',
                'subtitle'   => 'All orders fully insured via DHL Express. 3–5 day delivery globally.',
                'link_url'   => '/store/collections',
                'link_text'  => 'Browse All',
                'position'   => 'product',
                'sort_order' => 1,
                'status'     => true,
                'starts_at'  => null,
                'ends_at'    => null,
            ],
        ];

        foreach ($banners as $data) {
            Banner::updateOrCreate(
                ['title' => $data['title'], 'position' => $data['position']],
                $data
            );
        }

        $this->command->info('✓ Banners seeded');
    }
}
