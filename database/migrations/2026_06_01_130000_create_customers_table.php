<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer master. Sales reference this table; repeat customers are
     * tracked here so the team has full purchase history per customer.
     *
     * A "Walk-in Customer" record should be seeded by hand or via the UI
     * so quick-counter sales have somewhere to land — by design we do NOT
     * allow nullable customer_id on the sales table.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // ── Identification ───────────────────────────────────────────
            $table->string('customer_code', 50)->unique();
            $table->string('name', 191);
            $table->string('company_name', 191)->nullable();

            // retail | wholesale | walk_in
            $table->string('customer_type', 20)->default('retail')->index();

            // ── Contact ──────────────────────────────────────────────────
            $table->string('email', 191)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();

            // ── Tax / KYC (Indian retail context for high-value gem sales)
            $table->string('gst_number', 50)->nullable();
            $table->string('pan_number', 20)->nullable();

            // ── Address ──────────────────────────────────────────────────
            $table->string('address_line1', 191)->nullable();
            $table->string('address_line2', 191)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('zip_code', 20)->nullable();

            // ── Status + Audit ───────────────────────────────────────────
            $table->boolean('status')->default(true)->index();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Lookup indexes ───────────────────────────────────────────
            $table->index('name');
            $table->index('phone');
            $table->index('email');

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
