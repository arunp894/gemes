<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters:
     *   1. Permissions -- the catalogue must exist before roles can attach.
     *   2. Roles       -- creates roles and attaches permissions.
     *   3. AdminUser   -- creates the default admin and attaches the admin role.
     *   4. Channels    -- catalogue lookup data (unrelated to auth).
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ChannelSeeder::class,
        ]);
    }
}
