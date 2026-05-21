<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permissions table.
 *
 * Permission slugs follow the convention `module.action`
 * (e.g. `categories.view`, `products.edit`, `products.toggle-website`).
 * The `module` column groups permissions for the role-edit UI so
 * checkboxes can be rendered per module/section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Human label, e.g. "View Categories"
            $table->string('slug')->unique();             // Machine key, e.g. "categories.view"
            $table->string('module')->index();            // Grouping, e.g. "categories"
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
