<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('banners')) {
            return;
        }

        Schema::create('banners', function (Blueprint $table) {
            $table->id();

            $table->string('title', 191);
            $table->string('subtitle', 191)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('link_text', 100)->nullable();

            // Where the banner is displayed.
            // Values: 'home', 'category', 'product', 'promo'
            $table->string('position', 50)->default('home')->index();

            $table->unsignedSmallInteger('sort_order')->default(0)->index();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('status')->default(true)->index();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
