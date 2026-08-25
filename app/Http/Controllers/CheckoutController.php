<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Channel;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Services\CartService;
use App\Services\SettingService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * PayPal Checkout Controller — customer-auth aware.
 *
 * Flow:
 *  1. GET  /store/checkout         — redirect to login if no customer session
 *  2. POST /store/checkout/create  — create PayPal order (AJAX)
 *  3. POST /store/checkout/capture — capture + create Sale + deduct stock
 *  4. GET  /store/checkout/success — thank-you page
 *
 * Stock is deducted via StockService::recordSalePosting() which writes
 * OUT movements per location exactly as the back-office POS does.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly StockService   $stock,
        private readonly CartService    $cartService,
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
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json', 'Prefer' => 'return=representation'])
                ->post($this->apiBase() . '/v2/checkout/orders', [
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

            return response()->json(['orderID' => $response->json('id')]);

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
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->apiBase() . '/v2/checkout/orders/' . $request->orderID . '/capture');

            if ($response->failed()) {
                logger()->error('PayPal capture failed', ['body' => $response->body()]);
                return response()->json(['error' => 'Payment capture failed. Please contact support.'], 422);
            }

            $data   = $response->json();
            $status = $data['status'] ?? '';

            if ($status === 'COMPLETED') {
                // Create the ERP Sale record + deduct stock
                $sale = $this->createSaleFromCart($data['id']);

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

    /* ---------------------------------------------------------------
     |  Create ERP Sale from Cart (internal)
     | --------------------------------------------------------------- */

    /**
     * Convert the session cart into a posted Sale + SaleLines, then
     * trigger StockService::recordSalePosting() to write OUT movements
     * against the correct location(s).
     *
     * The 'online' location is preferred; falls back to default location.
     * The customer in session is used as the sale's customer_id.
     */
    private function createSaleFromCart(string $paypalOrderId): ?Sale
    {
        $cart     = session('sg_cart', []);
        $customer = auth('customer')->user();

        if (empty($cart) || ! $customer) {
            return null;
        }

        // Payment is already captured for the full cart by this point, so
        // items are NOT dropped here even if one became unavailable in the
        // (usually seconds-long) window since createOrder() last checked --
        // same "log but don't block" call as the stock-availability check
        // below; the store fulfils manually rather than leaving the
        // customer paid with nothing.
        $availability = $this->cartService->validate($cart);
        if ($availability['removed']) {
            logger()->warning('Checkout: cart item(s) became unavailable between order creation and capture', [
                'paypal_order_id' => $paypalOrderId,
                'customer_id'     => $customer->id,
                'items'           => $availability['removed'],
            ]);
        }

        // Resolve the location for this online sale
        $locationId = Location::where('type', 'online')->where('status', true)->value('id')
            ?? $this->stock->defaultLocationId();

        if (! $locationId) {
            logger()->error('Checkout: no online/default location found — skipping sale creation.');
            return null;
        }

        try {
            return DB::transaction(function () use ($cart, $customer, $locationId, $paypalOrderId) {
                $today    = Carbon::today();
                $subtotal = 0.0;

                // Build Sale header
                // Resolve the 'website' channel — set on all online orders
                $websiteChannelId = Channel::where('code', Channel::CODE_WEBSITE)->value('id');

                $sale = Sale::create([
                    'sale_number'    => Sale::generateSaleNumber($today),
                    'sale_date'      => $today,
                    'customer_id'    => $customer->id,
                    'location_id'    => $locationId,
                    'channel_id'     => $websiteChannelId,
                    'salesperson_id' => null,
                    'tax_type'       => Sale::TAX_NONE,
                    'subtotal'       => 0,
                    'tax_total'      => 0,
                    'discount_total' => 0,
                    'shipping_charge' => 0,
                    'shipping_status' => Sale::SHIPPING_PENDING,
                    'grand_total'    => 0,
                    'paid_amount'    => 0,
                    'balance_due'    => 0,
                    'payment_status' => Sale::PAY_UNPAID,
                    'status'         => Sale::STATUS_DRAFT,
                    'note'           => 'Online order. PayPal ID: ' . $paypalOrderId,
                ]);

                // Build SaleLines from cart
                foreach ($cart as $item) {
                    $qty   = max(1, (int) ($item['qty'] ?? 1));
                    $price = (float) $item['price'];
                    // Already price x qty (kept in sync by CartController::
                    // updateQty()) — reuse it rather than recompute, so the
                    // sale total always matches what was actually charged.
                    $lineTotal = (float) ($item['subtotal'] ?? ($price * $qty));
                    $subtotal += $lineTotal;

                    SaleLine::create([
                        'sale_id'             => $sale->id,
                        'product_id'          => $item['id'],
                        'purchase_product_id' => null, // FIFO allocated by StockService
                        'barcode'             => $item['sku'] ?? null,
                        'qty'                 => $qty,
                        // Whole-listing carat for this line, from the
                        // cart (set at add-to-cart time from the
                        // product's own recorded weight) — null for
                        // non-gemstone products, which never carry one.
                        'carat_weight'        => $item['carat'] ?? null,
                        'unit_price'          => $price,
                        'tax_percent'         => 0,
                        'tax_amount'          => 0,
                        'discount_percent'    => 0,
                        'discount_amount'     => 0,
                        'subtotal'            => $lineTotal,
                        'total'               => $lineTotal,
                    ]);
                }

                // Update totals
                $sale->update([
                    'subtotal'       => $subtotal,
                    'grand_total'    => $subtotal,
                    'paid_amount'    => $subtotal,
                    'balance_due'    => 0,
                    'payment_status' => Sale::PAY_PAID,
                ]);

                // Post the sale (draft → posted) + deduct stock via StockService
                $availabilityErrors = $this->stock->checkSaleAvailability($sale);

                if (! empty($availabilityErrors)) {
                    // Log but don't block — payment is already captured; fulfil manually
                    logger()->warning('Checkout stock shortage after payment capture', [
                        'sale_id' => $sale->id,
                        'errors'  => $availabilityErrors,
                    ]);
                } else {
                    // recordSalePosting writes OUT movements per location
                    $this->stock->recordSalePosting($sale);
                }

                // Mark as posted regardless (money is collected)
                $sale->update(['status' => Sale::STATUS_POSTED]);

                return $sale;
            });

        } catch (\Throwable $e) {
            logger()->error('Checkout sale creation failed', [
                'message'         => $e->getMessage(),
                'paypal_order_id' => $paypalOrderId,
            ]);
            return null;
        }
    }

    /* ---------------------------------------------------------------
     |  PayPal REST Helpers
     | --------------------------------------------------------------- */

    private function apiBase(): string
    {
        return $this->settings->get('paypal_mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getAccessToken(): string
    {
        $clientId = $this->settings->get('paypal_client_id', '');
        $secret   = $this->settings->get('paypal_secret', '');

        $response = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->apiBase() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            throw new \RuntimeException('Could not obtain PayPal access token.');
        }

        return $response->json('access_token');
    }
}
