<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Locations: physical places where sales happen.
     *
     * Stock is NOT tracked on this table. Per the design decision, stock at a
     * location is derived from sales events: future sales/transfer movements
     * will reference `location_id`, and on-hand balances will be computed from
     * the ledger. The Location module owns identity, address, and ownership
     * (manager) — nothing more.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            // ── Identification ───────────────────────────────────────────
            $table->string('location_code', 50)->unique();
            $table->string('name', 191);

            // warehouse | showroom | store | booth | exhibition | online | other
            // Enum kept short so it can be extended via an ALTER later.
            $table->string('type', 30)->default('warehouse')->index();

            $table->text('description')->nullable();

            // ── Ownership / Responsibility ───────────────────────────────
            // Manager is the "who" — the user responsible for this location.
            // Nullable so a location can exist before staff is assigned.
            $table->unsignedBigInteger('manager_id')->nullable()->index();

            // ── Address ──────────────────────────────────────────────────
            $table->string('address_line1', 191)->nullable();
            $table->string('address_line2', 191)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('zip_code', 20)->nullable();

            // ── Contact ──────────────────────────────────────────────────
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();

            // ── Geo (optional, for map pinning) ──────────────────────────
            // 10,7 fits any real-world lat/lng to ~1cm precision.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // ── Flags ────────────────────────────────────────────────────
            // Only one row may have is_default = true at a time — enforced
            // in the model (booted) so we don't fight a partial-unique index
            // across MySQL versions.
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('status')->default(true)->index();

            // ── Free-form notes ──────────────────────────────────────────
            $table->text('notes')->nullable();

            // ── Audit ────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────
            $table->index('name');
            $table->index('city');

            // ── Foreign keys ─────────────────────────────────────────────
            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
