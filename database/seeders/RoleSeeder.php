<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds the default role catalogue and attaches their permission sets.
 *
 * - admin    : is_super = true. Bypasses every permission check; the
 *              attached permission set below is purely for display.
 * - manager  : full CRUD across the catalogue modules, no user/role admin.
 * - operator : view + edit on catalogue modules. Cannot delete, cannot
 *              create new categories or products.
 * - viewer   : read-only across catalogue modules.
 *
 * Idempotent: upserts on `slug` and re-syncs the permission attachment.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Admin -- super role, gets the bypass.
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name'        => 'Administrator',
                'description' => 'Full access to every module. Bypasses permission checks.',
                'is_super'    => true,
            ],
        );
        // Attach every permission for display purposes; runtime checks are
        // short-circuited by is_super anyway.
        $admin->permissions()->sync(Permission::pluck('id'));

        // 2) Manager -- full CRUD across catalogue.
        $manager = Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name'        => 'Catalogue Manager',
                'description' => 'Full CRUD on categories, subcategories, products and barcodes.',
                'is_super'    => false,
            ],
        );
        $manager->syncPermissionsBySlug([
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'subcategories.view', 'subcategories.create', 'subcategories.edit', 'subcategories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete', 'products.toggle-website',
            'barcodes.view', 'barcodes.print',
        ]);

        // 3) Operator -- view + edit, no create / delete.
        $operator = Role::updateOrCreate(
            ['slug' => 'operator'],
            [
                'name'        => 'Operator',
                'description' => 'Day-to-day editing across catalogue modules. Cannot create or delete.',
                'is_super'    => false,
            ],
        );
        $operator->syncPermissionsBySlug([
            'categories.view', 'categories.edit',
            'subcategories.view', 'subcategories.edit',
            'products.view', 'products.edit', 'products.toggle-website',
            'barcodes.view', 'barcodes.print',
        ]);

        // 4) Viewer -- read-only.
        $viewer = Role::updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name'        => 'Viewer',
                'description' => 'Read-only access. Cannot modify anything.',
                'is_super'    => false,
            ],
        );
        $viewer->syncPermissionsBySlug([
            'categories.view',
            'subcategories.view',
            'products.view',
            'barcodes.view',
        ]);
    }
}
