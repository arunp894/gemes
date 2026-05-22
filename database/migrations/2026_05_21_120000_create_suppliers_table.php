<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('supplier_code', 50)->unique();
            $table->string('name', 191);
            $table->string('company_name', 191)->nullable();

            // Contact
            $table->string('email', 191)->nullable();
            $table->string('phone', 30);
            $table->string('alternate_phone', 30)->nullable();

            // Tax / Compliance
            $table->string('gst_number', 50)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('website', 191)->nullable();

            // Address
            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->text('address')->nullable();

            // Financial
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);

            // Status + Audit
            $table->boolean('status')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Useful lookup indexes
            $table->index('name');
            $table->index('phone');

            // Foreign keys
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
