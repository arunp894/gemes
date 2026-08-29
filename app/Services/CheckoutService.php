<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use App\Models\SaleLine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Converts a captured PayPal order into an ERP Sale + SaleLines, then
 * deducts stock via StockService — the same conversion CheckoutController
 * used to do inline, now shared with PaypalWebhookController.
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly StockService $stock,
    ) {}

    /**
     * $cart has the same shape as the storefront session cart: keyed by
     * product_id, each item carrying at least title/price/qty/subtotal/sku.
     *
     * Idempotent: a Sale is uniquely identified by (channel_id,
     * external_ref) where external_ref is the PayPal order id (see the
     * sales_channel_external_ref_unique index, added for eBay import
     * dedup and reused here). Calling this twice for the same PayPal
     * order id returns the existing Sale instead of creating a second
     * one — this matters because the direct browser capture call AND the
     * PayPal webhook can both race to convert the same paid order.
     */
    public function createSaleFromCart(Customer $customer, array $cart, string $paypalOrderId): ?Sale
    {
        if (empty($cart)) {
            return null;
        }

        $websiteChannelId = Channel::where('code', Channel::CODE_WEBSITE)->value('id');

        $existing = $this->findByPaypalOrderId($websiteChannelId, $paypalOrderId);
        if ($existing) {
            return $existing;
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
                'customer_id' => $customer->id,
                'items' => $availability['removed'],
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
            return DB::transaction(function () use ($cart, $customer, $locationId, $paypalOrderId, $websiteChannelId) {
                $today = Carbon::today();
                $subtotal = 0.0;

                $sale = Sale::create([
                    'sale_number' => Sale::generateSaleNumber($today),
                    'sale_date' => $today,
                    'customer_id' => $customer->id,
                    'location_id' => $locationId,
                    'channel_id' => $websiteChannelId,
                    'salesperson_id' => null,
                    'tax_type' => Sale::TAX_NONE,
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'discount_total' => 0,
                    'shipping_charge' => 0,
                    'shipping_status' => Sale::SHIPPING_PENDING,
                    'grand_total' => 0,
                    'paid_amount' => 0,
                    'balance_due' => 0,
                    'payment_status' => Sale::PAY_UNPAID,
                    'status' => Sale::STATUS_DRAFT,
                    'external_ref' => $paypalOrderId,
                    'note' => 'Online order. PayPal ID: '.$paypalOrderId,
                ]);

                // Build SaleLines from cart
                foreach ($cart as $item) {
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $price = (float) $item['price'];
                    // Already price x qty (kept in sync by CartController::
                    // updateQty()) — reuse it rather than recompute, so the
                    // sale total always matches what was actually charged.
                    $lineTotal = (float) ($item['subtotal'] ?? ($price * $qty));
                    $subtotal += $lineTotal;

                    SaleLine::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['id'],
                        'purchase_product_id' => null, // FIFO allocated by StockService
                        'barcode' => $item['sku'] ?? null,
                        'qty' => $qty,
                        // Whole-listing carat for this line, from the
                        // cart (set at add-to-cart time from the
                        // product's own recorded weight) — null for
                        // non-gemstone products, which never carry one.
                        'carat_weight' => $item['carat'] ?? null,
                        'unit_price' => $price,
                        'tax_percent' => 0,
                        'tax_amount' => 0,
                        'discount_percent' => 0,
                        'discount_amount' => 0,
                        'subtotal' => $lineTotal,
                        'total' => $lineTotal,
                    ]);
                }

                // Update totals
                $sale->update([
                    'subtotal' => $subtotal,
                    'grand_total' => $subtotal,
                    'paid_amount' => $subtotal,
                    'balance_due' => 0,
                    'payment_status' => Sale::PAY_PAID,
                ]);

                // Post the sale (draft → posted) + deduct stock via StockService
                $availabilityErrors = $this->stock->checkSaleAvailability($sale);

                if (! empty($availabilityErrors)) {
                    // Log but don't block — payment is already captured; fulfil manually
                    logger()->warning('Checkout stock shortage after payment capture', [
                        'sale_id' => $sale->id,
                        'errors' => $availabilityErrors,
                    ]);
                } else {
                    // recordSalePosting writes OUT movements per location
                    $this->stock->recordSalePosting($sale);
                }

                // Mark as posted regardless (money is collected)
                $sale->update(['status' => Sale::STATUS_POSTED]);

                return $sale;
            });

        } catch (QueryException $e) {
            // The other path (webhook vs. direct browser capture) won the
            // race and created the Sale first — the unique index on
            // (channel_id, external_ref) rejected this insert. Fetch and
            // return that Sale instead of treating it as a failure; the
            // payment is fine, it's just already been recorded.
            if (str_contains($e->getMessage(), 'sales_channel_external_ref_unique')) {
                return $this->findByPaypalOrderId($websiteChannelId, $paypalOrderId);
            }

            logger()->error('Checkout sale creation failed', [
                'message' => $e->getMessage(),
                'paypal_order_id' => $paypalOrderId,
            ]);

            return null;

        } catch (\Throwable $e) {
            logger()->error('Checkout sale creation failed', [
                'message' => $e->getMessage(),
                'paypal_order_id' => $paypalOrderId,
            ]);

            return null;
        }
    }

    private function findByPaypalOrderId(?int $websiteChannelId, string $paypalOrderId): ?Sale
    {
        return Sale::where('channel_id', $websiteChannelId)
            ->where('external_ref', $paypalOrderId)
            ->first();
    }
}
