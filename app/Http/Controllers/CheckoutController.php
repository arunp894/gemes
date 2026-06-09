<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * PayPal Checkout Controller
 *
 * Flow:
 *  1. GET  /store/checkout         — show checkout page with PayPal JS SDK
 *  2. POST /store/checkout/create  — create a PayPal order via REST API,
 *                                    return { orderID } as JSON
 *  3. POST /store/checkout/capture — capture approved order, return success/fail JSON
 *
 * Uses PayPal v2 Orders API (REST). No SDK dependency — raw HTTP calls
 * via Laravel HTTP client so nothing extra to install.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    /* ---------------------------------------------------------------
     |  Show checkout page
     | --------------------------------------------------------------- */

    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        $cart  = session('sg_cart', []);
        $total = array_sum(array_column($cart, 'subtotal'));

        if (empty($cart)) {
            return redirect()->route('website.cart.index')->with('error', 'Your cart is empty.');
        }

        $paypalEnabled  = $this->settings->bool('paypal_enabled');
        $paypalClientId = $this->settings->get('paypal_client_id', '');
        $paypalMode     = $this->settings->get('paypal_mode', 'sandbox');
        $currencyCode   = strtoupper($this->settings->get('currency_code', 'USD'));

        return view('website.checkout', compact(
            'cart', 'total',
            'paypalEnabled', 'paypalClientId', 'paypalMode', 'currencyCode',
        ));
    }

    /* ---------------------------------------------------------------
     |  Create PayPal Order  (AJAX — called by PayPal JS SDK button)
     | --------------------------------------------------------------- */

    public function createOrder(Request $request): JsonResponse
    {
        if (! $this->settings->bool('paypal_enabled')) {
            return response()->json(['error' => 'PayPal is not enabled.'], 422);
        }

        $cart         = session('sg_cart', []);
        $total        = array_sum(array_column($cart, 'subtotal'));
        $currencyCode = strtoupper($this->settings->get('currency_code', 'USD'));

        if (empty($cart) || $total <= 0) {
            return response()->json(['error' => 'Cart is empty.'], 422);
        }

        // Build line items
        $items = array_values(array_map(fn ($item) => [
            'name'        => $item['title'],
            'unit_amount' => ['currency_code' => $currencyCode, 'value' => number_format($item['price'], 2, '.', '')],
            'quantity'    => '1',
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
     |  Capture PayPal Order  (AJAX — called after buyer approval)
     | --------------------------------------------------------------- */

    public function captureOrder(Request $request): JsonResponse
    {
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
                // Clear cart on successful payment
                session()->forget('sg_cart');

                return response()->json([
                    'success'   => true,
                    'message'   => 'Payment successful! Your order has been placed.',
                    'order_id'  => $data['id'],
                    'status'    => $status,
                    'redirect'  => route('website.checkout.success', ['order' => $data['id']]),
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
        return view('website.checkout_success', compact('orderId'));
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
