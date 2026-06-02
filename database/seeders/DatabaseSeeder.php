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
     *   1. Permissions      -- the catalogue must exist before roles can attach.
     *   2. Roles            -- creates roles and attaches permissions.
     *   3. AdminUser        -- creates the default admin and attaches the admin role.
     *   4. Channels         -- sales channel lookup data.
     *   5. Locations        -- physical locations (warehouse, showroom). Must run
     *                         before any stock or purchase seeds.
     *   6. Suppliers        -- demo suppliers for purchase testing.
     *   7. Categories       -- gemstone category hierarchy.
     *   8. Racks            -- storage racks in the main warehouse.
     *   9. WalkInCustomer   -- default customer for the sales terminal.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            ChannelSeeder::class,
            LocationSeeder::class,
            SupplierSeeder::class,
            CategorySeeder::class,
            RackSeeder::class,
            WalkInCustomerSeeder::class,
        ]);
    }
}
