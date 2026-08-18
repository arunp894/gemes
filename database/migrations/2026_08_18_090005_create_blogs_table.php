<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blog posts — managed in admin, displayed on the public storefront.
     * `status` doubles as the publish flag (no separate date window,
     * unlike Banner) — a post is live on the front end whenever
     * status = true. `published_at` is stamped the first time a post
     * goes live and drives the front-end sort order.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            $table->string('title', 191);
            $table->string('slug', 191)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');

            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->boolean('status')->default(true)->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
