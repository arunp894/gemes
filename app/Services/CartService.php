<?php

namespace App\Services;

use App\Models\Product;

/**
 * Re-validates session-cart items against live Product state.
 *
 * The storefront cart caches title/price/thumb on the item at
 * add-to-cart time (see CartController::add()), so a product that gets
 * unlisted (website_enabled -> false), deactivated (status -> draft),
 * or has its online price cleared on the admin side afterwards is
 * otherwise invisible to the cart/checkout flow -- the stale session
 * data would still let checkout proceed. This is the single place that
 * re-checks the same "purchasable online" rule CartController::add()
 * applies at add-time, so it can't drift out of sync between the two.
 *
 * Used by CartController (cart page) and CheckoutController (checkout
 * page + before creating the PayPal order), so a product can't be paid
 * for after it's been pulled from the website.
 */
class CartService
{
    /**
     * Split $cart into still-purchasable items and ones that no longer
     * qualify (missing/deleted, delisted, deactivated, or no online
     * price). Pure function -- does NOT touch the session; callers
     * persist the pruned cart themselves via their own saveCart()/
     * session helpers when $removed is non-empty.
     *
     * @param  array<int, array<string, mixed>>  $cart  keyed by product_id, same shape as CartController's session cart
     * @return array{cart: array<int, array<string, mixed>>, removed: string[]}
     */
    public function validate(array $cart): array
    {
        if (empty($cart)) {
            return ['cart' => $cart, 'removed' => []];
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $removed  = [];

        foreach ($cart as $id => $item) {
            $product = $products->get($id);

            if (! $product || ! $product->isPurchasableOnline()) {
                $removed[] = $item['title'] ?? ('Item #' . $id);
                unset($cart[$id]);
            }
        }

        return ['cart' => $cart, 'removed' => $removed];
    }
}
