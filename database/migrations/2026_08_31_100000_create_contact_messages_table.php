<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Submissions from the storefront's public Contact Us form.
     * No auth guard on that form (any visitor can submit), so there's no
     * customer_id — just the details they typed in.
     */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->string('name', 191);
            $table->string('email', 191);
            $table->string('phone', 30)->nullable();
            $table->text('message');

            $table->boolean('is_read')->default(false)->index();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
