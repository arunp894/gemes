<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'box' to the existing ENUM. Keep legacy values for any
        // historical rows that may already use them.
        DB::statement("
            ALTER TABLE purchase_lines
            MODIFY COLUMN type ENUM('piece', 'box', 'unit', 'carton') NOT NULL
        ");
    }

    public function down(): void
    {
        // First migrate any 'box' rows back to a legacy value before
        // shrinking the enum, otherwise MySQL refuses.
        DB::statement("UPDATE purchase_lines SET type = 'unit' WHERE type = 'box'");

        DB::statement("
            ALTER TABLE purchase_lines
            MODIFY COLUMN type ENUM('piece', 'unit', 'carton') NOT NULL
        ");
    }
};
