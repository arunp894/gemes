<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a default admin user and attaches the `admin` role to it.
 *
 * Credentials (CHANGE IN PRODUCTION):
 *   email    : admin@paces.local
 *   password : password
 *
 * Idempotent: upserts on email, re-attaches admin role each run.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@paces.local'],
            [
                'name'              => 'Site Administrator',
                'password'          => Hash::make('password'),
                'is_active'         => true,
                'email_verified_at' => now(),
            ],
        );

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
