<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds the two pages requested for the storefront footer/nav
 * (About Us, Terms & Conditions) so the admin Pages screen and the
 * public /pages/{slug} route have something to show on first boot.
 * Idempotent — matched by slug, safe to re-run.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => Page::SLUG_ABOUT_US],
            [
                'title'   => 'About Us',
                'content' => "<p>Sukaina Gems specializes in rare and precious gemstones, with a particular "
                    . "focus on Paraiba Tourmaline and Tanzanite. We work directly with trusted suppliers to "
                    . "bring ethically sourced stones to collectors and jewelers alike.</p>"
                    . "<p>Edit this page from Admin &rarr; Settings &rarr; Pages.</p>",
            ],
        );

        Page::updateOrCreate(
            ['slug' => Page::SLUG_TERMS_CONDITIONS],
            [
                'title'   => 'Terms & Conditions',
                'content' => "<p>These terms and conditions govern your use of the Sukaina Gems website and "
                    . "any purchases made through it. By placing an order, you agree to these terms.</p>"
                    . "<p>Edit this page from Admin &rarr; Settings &rarr; Pages.</p>",
            ],
        );
    }
}
