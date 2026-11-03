<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rentals')) {
            Schema::create('rentals', function (Blueprint $table) {
                $table->id();
                $table->string('rent_number', 30)->unique();
                $table->foreignId('customer_id')->constrained()->restrictOnDelete();
                $table->foreignId('location_id')->constrained()->restrictOnDelete();
                $table->dateTime('start_at');
                $table->dateTime('due_at')->index();
                $table->dateTime('returned_at')->nullable();
                $table->string('status', 20)->default('active')->index(); // active | returned | cancelled
                $table->decimal('rent_total', 12, 2)->default(0);
                $table->decimal('deposit_total', 12, 2)->default(0);
                $table->decimal('rent_paid', 12, 2)->default(0);
                $table->decimal('deposit_paid', 12, 2)->default(0);
                $table->decimal('deposit_refunded', 12, 2)->default(0);
                $table->decimal('deduction_total', 12, 2)->default(0); // late fee / damage kept from the deposit
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('rental_lines')) {
            Schema::create('rental_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('purchase_product_id');
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('carat_weight', 10, 3)->nullable();
                $table->decimal('rent_amount', 12, 2)->default(0);
                $table->decimal('deposit_amount', 12, 2)->default(0);
                $table->string('status', 20)->default('out')->index(); // out | returned | lost
                $table->dateTime('returned_at')->nullable();
                $table->decimal('deduction', 12, 2)->default(0);
                $table->string('note', 255)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
                $table->foreign('purchase_product_id')->references('id')->on('purchase_products')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('rental_payments')) {
            Schema::create('rental_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20)->index();           // rent | deposit | refund
                $table->string('payment_method', 30)->default('cash');
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('rental_logs')) {
            Schema::create('rental_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 30);                  // created | extended | returned | lost | cancelled
                $table->text('detail')->nullable();
                $table->timestamps();
            });
        }

        // Extend the stock_movements.reason ENUM, keeping whatever values it already has.
        $type = DB::selectOne("SHOW COLUMNS FROM stock_movements LIKE 'reason'")->Type;
        preg_match_all("/'([^']+)'/", $type, $m);
        $values = array_values(array_unique(array_merge($m[1], ['rent_out', 'rent_return'])));
        $list = implode(',', array_map(fn ($v) => "'{$v}'", $values));
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN reason ENUM({$list}) NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_logs');
        Schema::dropIfExists('rental_payments');
        Schema::dropIfExists('rental_lines');
        Schema::dropIfExists('rentals');
    }
};
