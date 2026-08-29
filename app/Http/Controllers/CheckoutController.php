<?php

namespace App\Http\Controllers;

use App\Models\PaypalOrder;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaypalService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * PayPal Checkout Controller — customer-auth aware.
 *
 * Flow:
 *  1. GET  /checkout         — redirect to login if no customer session
 *  2. POST /checkout/create  — create PayPal order (AJAX)
 *  3. POST /checkout/capture — capture + create Sale + deduct stock
 *  4. GET  /checkout/success — thank-you page
 *
 * There's also an async fallback: PaypalWebhookController handles
 * PAYMENT.CAPTURE.COMPLETED events for cases where step 3 never lands
 * (browser closed, network drop after payment). Both paths share Sale
 * creation via CheckoutService, which is idempotent per PayPal order id.
 *
 * Stock is deducted via StockService::recordSalePosting() (inside
 * CheckoutService), which writes OUT movements per location exactly as
 * the back-office POS does.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly SettingService  $settings,
        private readonly CartService     $cartService,
        private readonly CheckoutService $checkout,
        private readonly PaypalService   $paypal,
    ) {}

    /* ---------------------------------------------------------------
     |  Show checkout page (requires customer login)
     | --------------------------------------------------------------- */

    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        // Re-check every item against the product's CURRENT
        // website_enabled/status/price before showing the payment page
        // -- see CartController::index() for why (same rule, same
        // CartService).
        $result = $this->cartService->validate(session('sg_cart', []));
        if ($result['removed']) {
            session(['sg_cart' => $result['cart']]);
        }

        $cart  = $result['cart'];
        $total = array_sum(array_column($cart, 'subtotal'));

        if (empty($cart)) {
            return redirect()->route('website.cart.index')->with('error', 'Your cart is empty.');
        }

        // Guest check — store intended URL and send to login
        if (! auth('customer')->check()) {
            session()->put('url.customer_intended', route('website.checkout.index'));
            return redirect()->route('website.auth.login')
                ->with('info', 'Please log in or create an account to checkout.');
        }

        $customer       = auth('customer')->user();
        $paypalEnabled  = $this->settings->bool('paypal_enabled');
        $paypalClientId = $this->settings->get('paypal_client_id', '');
        $paypalMode     = $this->settings->get('paypal_mode', 'sandbox');
        $currencyCode   = strtoupper($this->settings->get('currency_code', 'USD'));

        return view('website.checkout', compact(
            'cart', 'total', 'customer',
            'paypalEnabled', 'paypalClientId', 'paypalMode', 'currencyCode',
        ))->with('removedItems', $result['removed']);
    }

    /* ---------------------------------------------------------------
     |  Create PayPal Order  (AJAX)
     | --------------------------------------------------------------- */

    public function createOrder(Request $request): JsonResponse
    {
        if (! auth('customer')->check()) {
            return response()->json(['error' => 'Please log in to checkout.'], 401);
        }

        if (! $this->settings->bool('paypal_enabled')) {
            return response()->json(['error' => 'PayPal is not enabled.'], 422);
        }

        // Hard gate before any money moves: re-check every cart item
        // against the product's CURRENT website_enabled/status/price.
        // Without this, a product pulled from the site after being added
        // to the cart could still be paid for.
        $result = $this->cartService->validate(session('sg_cart', []));
        if ($result['removed']) {
            session(['sg_cart' => $result['cart']]);
            return response()->json([
                'error' => 'The following item(s) are no longer available and were removed from your cart: '
                    . implode(', ', $result['removed']) . '. Please review your cart and try again.',
            ], 422);
        }

        $cart         = $result['cart'];
        $total        = array_sum(array_column($cart, 'subtotal'));
        $currencyCode = strtoupper($this->settings->get('currency_code', 'USD'));

        if (empty($cart) || $total <= 0) {
            return response()->json(['error' => 'Cart is empty.'], 422);
        }

        $items = array_values(array_map(fn ($item) => [
            'name'        => $item['title'],
            'unit_amount' => ['currency_code' => $currencyCode, 'value' => number_format($item['price'], 2, '.', '')],
            // Must match the cart's real qty — PayPal requires
            // sum(unit_amount x quantity) across items to equal the
            // order's item_total below, which IS built from the full
            // qty-multiplied subtotal.
            'quantity'    => (string) max(1, (int) ($item['qty'] ?? 1)),
            'sku'         => $item['sku'] ?? null,
        ], $cart));

        try {
            $accessToken = $this->paypal->getAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json', 'Prefer' => 'return=representation'])
                ->post($this->paypal->apiBase() . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => $currencyCode,
                            'value'         => number_format($total, 2, '.', ''),
                            'breakdown'     => [
                                'item_total' => [
                                    'currency_code' => $currencyCode,
                                    'value'         => number_format($total, 2, '.', ''),
                                ],
                            ],
                        ],
                        'items'       => $items,
                        'description' => 'Sukaina Gems Order',
                    ]],
                ]);

            if ($response->failed()) {
                logger()->error('PayPal create order failed', ['body' => $response->body()]);
                return response()->json(['error' => 'Could not create PayPal order. Please try again.'], 422);
            }

            $orderId = $response->json('id');

            // Snapshot who/what this order is for, keyed by PayPal's order
            // id, so PaypalWebhookController can reconstruct the Sale later
            // even with no session at all (see PaypalOrder migration).
            PaypalOrder::create([
                'paypal_order_id' => $orderId,
                'customer_id'     => auth('customer')->id(),
                'cart_snapshot'   => $cart,
            ]);

            return response()->json(['orderID' => $orderId]);

        } catch (\Throwable $e) {
            logger()->error('PayPal create order exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'PayPal service unavailable.'], 500);
        }
    }

    /* ---------------------------------------------------------------
     |  Capture PayPal Order — then create Sale + deduct stock
     | --------------------------------------------------------------- */

    public function captureOrder(Request $request): JsonResponse
    {
        if (! auth('customer')->check()) {
            return response()->json(['error' => 'Please log in to checkout.'], 401);
        }

        $request->validate(['orderID' => ['required', 'string']]);

        try {
            $accessToken = $this->paypal->getAccessToken();

            // ->post($url) with no second argument still sends a body —
            // Laravel's HTTP client defaults to json_encode([]), i.e. the
            // literal 2 bytes "[]" (a JSON array). PayPal's capture
            // endpoint expects an object-shaped body and was rejecting
            // that array as MALFORMED_REQUEST_JSON. Passing (object) []
            // makes json_encode emit "{}" instead.
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->paypal->apiBase() . '/v2/checkout/orders/' . $request->orderID . '/capture', (object) []);

            if ($response->failed()) {
                logger()->error('PayPal capture failed', ['body' => $response->body()]);
                return response()->json(['error' => 'Payment capture failed. Please contact support.'], 422);
            }

            $data   = $response->json();
            $status = $data['status'] ?? '';

            if ($status === 'COMPLETED') {
                // Create the ERP Sale record + deduct stock. Idempotent —
                // if the PayPal webhook already converted this same order
                // (race with this request), this returns that Sale instead
                // of creating a duplicate.
                $sale = $this->checkout->createSaleFromCart(
                    auth('customer')->user(),
                    session('sg_cart', []),
                    $data['id'],
                );

                session()->forget('sg_cart');

                return response()->json([
                    'success'  => true,
                    'message'  => 'Payment successful! Your order has been placed.',
                    'order_id' => $data['id'],
                    'sale_id'  => $sale?->id,
                    'status'   => $status,
                    'redirect' => route('website.checkout.success', [
                        'order'   => $data['id'],
                        'sale_id' => $sale?->id,
                    ]),
                ]);
            }

            return response()->json(['error' => 'Payment not completed. Status: ' . $status], 422);

        } catch (\Throwable $e) {
            logger()->error('PayPal capture exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'PayPal service unavailable.'], 500);
        }
    }

    /* ---------------------------------------------------------------
     |  Order Success Page
     | --------------------------------------------------------------- */

    public function success(Request $request): View
    {
        $orderId = $request->get('order', 'N/A');
        $saleId  = $request->get('sale_id');
        $sale    = null;

        if ($saleId && auth('customer')->check()) {
            $sale = auth('customer')->user()
                ->sales()
                ->with('lines.product')
                ->find($saleId);
        }

        return view('website.checkout_success', compact('orderId', 'sale'));
    }
}
