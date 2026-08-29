<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots the customer + cart behind a PayPal order at the moment it's
 * created (CheckoutController::createOrder()), keyed by PayPal's own order
 * id.
 *
 * The direct browser flow (POST /checkout/capture) never needs this row —
 * it already has the customer's session and cart in hand. It exists so the
 * PayPal webhook (PaypalWebhookController), which runs as a server-to-server
 * call with no session at all, can still reconstruct "what was this order,
 * for whom" if a PAYMENT.CAPTURE.COMPLETED event arrives for an order the
 * direct capture call never finished converting into a Sale (browser tab
 * closed after payment, network drop, etc).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paypal_orders', function (Blueprint $table) {
            $table->id();

            $table->string('paypal_order_id', 64)->unique();
            $table->unsignedBigInteger('customer_id')->index();
            $table->json('cart_snapshot');

            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')->on('customers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paypal_orders');
    }
};
