<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * Seed the four sales channels the platform supports.
     * Uses updateOrCreate keyed on `code` so re-running the seeder
     * is idempotent and won't create duplicates.
     */
    public function run(): void
    {
        $channels = [
            [
                'name'          => 'eBay',
                'code'          => Channel::CODE_EBAY,
                'icon'          => 'ti ti-brand-ebay',
                'status'        => true,
                'display_order' => 1,
            ],
            [
                'name'          => 'Catawiki',
                'code'          => Channel::CODE_CATAWIKI,
                'icon'          => 'ti ti-gavel',
                'status'        => true,
                'display_order' => 2,
            ],
            [
                'name'          => 'Website',
                'code'          => Channel::CODE_WEBSITE,
                'icon'          => 'ti ti-world-www',
                'status'        => true,
                'display_order' => 3,
            ],
            [
                'name'          => 'Sukainagems',
                'code'          => Channel::CODE_SUKAINAGEMS,
                'icon'          => 'ti ti-diamond',
                'status'        => true,
                'display_order' => 4,
            ],
            [
                'name'          => 'POS',
                'code'          => Channel::CODE_POS,
                'icon'          => 'ti ti-cash-register',
                'status'        => true,
                'display_order' => 5,
            ],
        ];

        foreach ($channels as $channel) {
            Channel::updateOrCreate(
                ['code' => $channel['code']],
                $channel
            );
        }
    }
}
