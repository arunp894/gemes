<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical permission catalogue.
 *
 * Slug convention: "module.action".
 * Modules align with sidebar sections and controller groupings so the
 * role-edit UI can render checkboxes grouped by module.
 *
 * Idempotent: re-running the seeder upserts on `slug` and leaves
 * existing role assignments alone.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ----- Categories module -----
            ['slug' => 'categories.view',          'name' => 'View Categories',          'module' => 'categories'],
            ['slug' => 'categories.create',        'name' => 'Create Categories',        'module' => 'categories'],
            ['slug' => 'categories.edit',          'name' => 'Edit Categories',          'module' => 'categories'],
            ['slug' => 'categories.delete',        'name' => 'Delete Categories',        'module' => 'categories'],

            // ----- Subcategories module -----
            ['slug' => 'subcategories.view',       'name' => 'View Subcategories',       'module' => 'subcategories'],
            ['slug' => 'subcategories.create',     'name' => 'Create Subcategories',     'module' => 'subcategories'],
            ['slug' => 'subcategories.edit',       'name' => 'Edit Subcategories',       'module' => 'subcategories'],
            ['slug' => 'subcategories.delete',     'name' => 'Delete Subcategories',     'module' => 'subcategories'],

            // ----- Products module -----
            ['slug' => 'products.view',            'name' => 'View Products',            'module' => 'products'],
            ['slug' => 'products.create',          'name' => 'Create Products',          'module' => 'products'],
            ['slug' => 'products.edit',            'name' => 'Edit Products',            'module' => 'products'],
            ['slug' => 'products.delete',          'name' => 'Delete Products',          'module' => 'products'],
            ['slug' => 'products.toggle-website',  'name' => 'Toggle Website Visibility','module' => 'products'],

            // ----- Barcodes (sub-feature of products, but distinct enough to gate separately) -----
            ['slug' => 'barcodes.view',            'name' => 'View Barcodes',            'module' => 'barcodes'],
            ['slug' => 'barcodes.print',           'name' => 'Print Barcode Labels',     'module' => 'barcodes'],

            // ----- User & Role administration -----
            ['slug' => 'users.view',               'name' => 'View Users',               'module' => 'users'],
            ['slug' => 'users.create',             'name' => 'Create Users',             'module' => 'users'],
            ['slug' => 'users.edit',               'name' => 'Edit Users',               'module' => 'users'],
            ['slug' => 'users.delete',             'name' => 'Delete Users',             'module' => 'users'],

            ['slug' => 'roles.view',               'name' => 'View Roles',               'module' => 'roles'],
            ['slug' => 'roles.create',             'name' => 'Create Roles',             'module' => 'roles'],
            ['slug' => 'roles.edit',               'name' => 'Edit Roles',               'module' => 'roles'],
            ['slug' => 'roles.delete',             'name' => 'Delete Roles',             'module' => 'roles'],
        ];

        foreach ($permissions as $row) {
            Permission::updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }
    }
}
