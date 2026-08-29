<?php

namespace App\Http\Controllers;

use App\Models\PaypalOrder;
use App\Services\CheckoutService;
use App\Services\PaypalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives PayPal's server-to-server webhook calls — the async fallback to
 * the direct browser flow in CheckoutController.
 *
 * Why this exists: CheckoutController::captureOrder() creates the Sale
 * synchronously right after the browser's capture call succeeds. If that
 * request never lands (tab closed right after paying, network drop, the
 * customer's own connection dying mid-response) PayPal still has the
 * customer's money but this app never finds out — no Sale, no stock
 * deducted, nothing to fulfil. PayPal's PAYMENT.CAPTURE.COMPLETED webhook
 * event is the reconciliation path for exactly that gap.
 *
 * Every event is verified against PayPal's own servers before anything in
 * it is trusted (see PaypalService::verifyWebhookSignature()) — this
 * endpoint has no other auth, since PayPal is calling it directly, not a
 * logged-in customer.
 *
 * Sale creation is shared with the direct flow via CheckoutService, which
 * is idempotent per PayPal order id — so it doesn't matter whether the
 * direct capture request or this webhook lands first; whichever arrives
 * second is a no-op that returns the already-created Sale.
 */
class PaypalWebhookController extends Controller
{
    private const EVENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';

    public function __construct(
        private readonly PaypalService $paypal,
        private readonly CheckoutService $checkout,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $event = $request->json()->all();

        $headers = [
            'transmission_id' => $request->header('Paypal-Transmission-Id'),
            'transmission_time' => $request->header('Paypal-Transmission-Time'),
            'cert_url' => $request->header('Paypal-Cert-Url'),
            'auth_algo' => $request->header('Paypal-Auth-Algo'),
            'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
        ];

        if (! $this->paypal->verifyWebhookSignature($headers, $event)) {
            logger()->warning('PayPal webhook: signature verification failed — event ignored.', [
                'event_type' => $event['event_type'] ?? null,
                'event_id' => $event['id'] ?? null,
            ]);

            // 400, not 401/403: PayPal doesn't authenticate itself beyond
            // the signature, so there's no "who are you" to distinguish —
            // this just tells PayPal the payload didn't check out.
            return response()->json(['error' => 'Signature verification failed.'], 400);
        }

        $eventType = $event['event_type'] ?? null;

        // Only PAYMENT.CAPTURE.COMPLETED means money actually moved.
        // CHECKOUT.ORDER.APPROVED (and others) fire earlier in the flow —
        // acknowledge them so PayPal stops retrying, but there's nothing
        // to convert into a Sale yet.
        if ($eventType !== self::EVENT_CAPTURE_COMPLETED) {
            return response()->json(['status' => 'ignored', 'event_type' => $eventType]);
        }

        $orderId = $event['resource']['supplementary_data']['related_ids']['order_id'] ?? null;

        if (! $orderId) {
            logger()->error('PayPal webhook: PAYMENT.CAPTURE.COMPLETED with no order_id in payload.', [
                'event_id' => $event['id'] ?? null,
            ]);

            return response()->json(['error' => 'No order_id in event.'], 422);
        }

        $paypalOrder = PaypalOrder::with('customer')->where('paypal_order_id', $orderId)->first();

        if (! $paypalOrder) {
            // Nothing snapshotted for this order id — either it predates
            // this table, or it's not one this storefront created. Ack
            // rather than error so PayPal doesn't retry indefinitely for
            // something this app can never resolve.
            logger()->warning('PayPal webhook: no PaypalOrder snapshot found — cannot reconstruct Sale.', [
                'paypal_order_id' => $orderId,
            ]);

            return response()->json(['status' => 'ignored', 'reason' => 'unknown_order']);
        }

        $sale = $this->checkout->createSaleFromCart(
            $paypalOrder->customer,
            $paypalOrder->cart_snapshot,
            $orderId,
        );

        if (! $sale) {
            logger()->error('PayPal webhook: createSaleFromCart returned null.', [
                'paypal_order_id' => $orderId,
            ]);

            return response()->json(['error' => 'Sale creation failed.'], 500);
        }

        return response()->json(['status' => 'ok', 'sale_id' => $sale->id]);
    }
}
