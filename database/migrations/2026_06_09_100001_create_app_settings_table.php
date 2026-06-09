<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->text('value')->nullable();
            $table->string('group', 60)->default('general')->index();
            $table->timestamps();
        });

        // Seed defaults immediately so the app works on first boot
        DB::table('app_settings')->insert([
            // ── General / Storefront ──────────────────────────────
            ['key' => 'site_name',            'value' => 'Sukaina Gems',  'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_tagline',         'value' => 'Specialists in Paraiba Tourmaline and Tanzanite', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_symbol',      'value' => '₹',             'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_code',        'value' => 'USD',           'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_position',    'value' => 'before',        'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_email',        'value' => '',              'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_phone',        'value' => '',              'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contact_whatsapp',     'value' => '',              'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cart_enabled',         'value' => '1',             'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'checkout_enabled',     'value' => '1',             'group' => 'general', 'created_at' => now(), 'updated_at' => now()],

            // ── PayPal ────────────────────────────────────────────
            ['key' => 'paypal_enabled',       'value' => '0',             'group' => 'paypal',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_mode',          'value' => 'sandbox',       'group' => 'paypal',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_client_id',     'value' => '',              'group' => 'paypal',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_secret',        'value' => '',              'group' => 'paypal',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_webhook_id',    'value' => '',              'group' => 'paypal',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
