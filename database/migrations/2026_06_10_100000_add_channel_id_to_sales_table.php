<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add channel_id (nullable FK → channels) to the sales table.
     *
     * Nullable because:
     *   - Existing sales have no channel assigned yet.
     *   - Some sale types (e.g. walk-in) may legitimately have no channel.
     *
     * Guarded — only adds the column if it doesn't already exist.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'channel_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->unsignedBigInteger('channel_id')
                    ->nullable()
                    ->after('location_id')
                    ->index();

                $table->foreign('channel_id')
                    ->references('id')
                    ->on('channels')
                    ->nullOnDelete(); // If channel is soft-deleted the FK still resolves;
                                     // but if a hard delete were ever done, set null rather than cascade.
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id');
        });
    }
};
