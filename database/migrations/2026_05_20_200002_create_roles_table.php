<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles table.
 *
 * - `slug` is the stable machine identifier used in seeders and middleware
 *   route declarations (e.g. ->middleware('role:admin')); `name` is the
 *   human label shown in the UI.
 * - `is_super` short-circuits permission checks: any user holding a super
 *   role passes every permission gate without an explicit grant. This is
 *   how the seeded `admin` role becomes the catch-all super user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_super')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
