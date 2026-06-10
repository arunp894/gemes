<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Laravel Authenticatable columns to the customers table so that
 * customers can register and log in on the storefront independently
 * from the back-office User model.
 *
 * Columns added:
 *   password        — bcrypt hash; nullable so existing rows aren't broken
 *   remember_token  — standard Laravel "remember me" column
 *   email_verified_at — optional; nullable by default
 *
 * Safe on both fresh and existing databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Only add if not already present (idempotent on re-runs)
            if (! Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (! Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            if (! Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
        });
    }
};
