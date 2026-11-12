<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rental_lines', 'rent_rate')) {
            Schema::table('rental_lines', function (Blueprint $table) {
                // Rate per CT (or per piece when the stone has no carat); rent_amount = CT x rate.
                $table->decimal('rent_rate', 12, 2)->default(0)->after('carat_weight');
            });
        }
    }

    public function down(): void
    {
        Schema::table('rental_lines', function (Blueprint $table) {
            $table->dropColumn('rent_rate');
        });
    }
};
