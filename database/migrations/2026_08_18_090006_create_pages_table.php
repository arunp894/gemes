<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic static-content pages managed in admin and rendered at
     * /pages/{slug} (see WebsiteController::pageShow()). Seeded with
     * 'about-us' and 'terms-conditions' (PageSeeder) but not limited to
     * them — new rows are automatically reachable without a route or
     * code change, since the front-end route binds on slug.
     *
     * No status/publish flag, unlike Blog: a page that exists is live.
     * Keep this simple unless a draft workflow is actually asked for.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            $table->string('slug', 191)->unique();
            $table->string('title', 191);
            $table->longText('content');

            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
