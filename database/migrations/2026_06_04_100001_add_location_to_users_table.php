<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Originally added a plain string `location` column to users.
 * Superseded by migration 2026_06_04_100002 which creates the proper
 * `location_user` pivot. This migration is now a guarded no-op so that
 * databases which never ran the string-column version do not fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op: superseded by 2026_06_04_100002_create_location_user_table.
        // The newer migration drops the column if it exists and creates the pivot.
    }

    public function down(): void
    {
        // No-op: paired down() lives in the superseding migration.
    }
};
