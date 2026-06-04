<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the earlier string `location` column with a proper many-to-many
 * relationship via the `location_user` pivot.
 *
 * A user may be assigned to multiple locations (e.g. a manager who covers
 * both the warehouse and the showroom), hence the pivot rather than a
 * single FK.
 *
 * The up() guard handles both possible states of the database:
 *   (a) Migration was never run  → no string column to drop, just create pivot.
 *   (b) Previous string-column migration ran → drop column first, then pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the stale string column if it exists (from the earlier migration).
        if (Schema::hasColumn('users', 'location')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }

        Schema::create('location_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('location_id');
            $table->timestamps();

            $table->primary(['user_id', 'location_id']);

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('location_id')
                ->references('id')->on('locations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');

        // Restore the string column so the previous migration's down() works.
        if (!Schema::hasColumn('users', 'location')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('location', 150)->nullable()->after('is_active');
            });
        }
    }
};
