<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Session-backed shopping cart for the Sukaina Gems storefront.
 *
 * Cart structure in session['sg_cart']:
 *   [
 *     product_id => [
 *       'id'       => int,
 *       'title'    => string,
 *       'price'    => float,
 *       'thumb'    => string|null,
 *       'qty'      => int,
 *       'subtotal' => float,
 *     ],
 *     ...
 *   ]
 *
 * Gem items are unique (each is a distinct stone), so qty is always 1
 * and adding the same product again is silently ignored.
 */
class CartController extends Controller
{
    private const SESSION_KEY = 'sg_cart';

    public function __construct(private readonly SettingService $settings) {}

    /* ---------------------------------------------------------------
     |  View Cart
     | --------------------------------------------------------------- */

    public function index(): View
    {
        $cart  = $this->getCart();
        $total = $this->cartTotal($cart);
        return view('website.cart', compact('cart', 'total'));
    }

    /* ---------------------------------------------------------------
     |  Add to Cart
     | --------------------------------------------------------------- */

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->settings->bool('cart_enabled', true)) {
            return $this->respond($request, false, 'Cart is currently disabled.', 0);
        }

        $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']]);

        $product = Product::findOrFail($request->product_id);

        // Only website-enabled, active products can be added
        if (! $product->website_enabled || ! $product->status) {
            return $this->respond($request, false, 'Product not available.', 0);
        }

        if (! $product->website_price) {
            return $this->respond($request, false, 'This gem requires an enquiry — no online price set.', 0);
        }

        $cart = $this->getCart();

        // Gems are unique per-piece; silently skip duplicates
        if (! isset($cart[$product->id])) {
            $cart[$product->id] = [
                'id'       => $product->id,
                'title'    => $product->display_website_title,
                'price'    => (float) $product->website_price,
                'thumb'    => $product->primary_thumb_url,
                'qty'      => 1,
                'subtotal' => (float) $product->website_price,
                'carat'    => $product->carat_weight,
                'sku'      => $product->sku,
            ];
        }

        $this->saveCart($cart);

        return $this->respond($request, true, 'Added to cart.', count($cart));
    }

    /* ---------------------------------------------------------------
     |  Remove from Cart
     | --------------------------------------------------------------- */

    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['product_id' => ['required', 'integer']]);

        $cart = $this->getCart();
        unset($cart[$request->product_id]);
        $this->saveCart($cart);

        return $this->respond($request, true, 'Removed from cart.', count($cart));
    }

    /* ---------------------------------------------------------------
     |  Clear Cart
     | --------------------------------------------------------------- */

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->saveCart([]);
        return $this->respond($request, true, 'Cart cleared.', 0);
    }

    /* ---------------------------------------------------------------
     |  Cart count (AJAX endpoint used by navbar badge)
     | --------------------------------------------------------------- */

    public function count(): JsonResponse
    {
        return response()->json(['count' => count($this->getCart())]);
    }

    /* ---------------------------------------------------------------
     |  Cart data for drawer AJAX reload
     | --------------------------------------------------------------- */

    public function data(): JsonResponse
    {
        $cart  = $this->getCart();
        $total = $this->cartTotal($cart);
        $html  = '';

        if (empty($cart)) {
            $html = '<div style="text-align:center;padding:60px 0;color:var(--white-faint)"><div style="font-size:40px;margin-bottom:14px">💎</div><p style="font-size:14px">Your cart is empty.</p></div>';
        } else {
            foreach ($cart as $item) {
                $thumb = $item['thumb']
                    ? '<img src="' . e($item['thumb']) . '" alt="" style="width:100%;height:100%;object-fit:cover">'
                    : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center"><div style="width:24px;height:24px;clip-path:polygon(50% 0%,93% 25%,93% 75%,50% 100%,7% 75%,7% 25%);background:linear-gradient(135deg,var(--teal-300),var(--teal-700))"></div></div>';
                $priceFormatted = $this->settings->formatPrice($item['price']);

                $html .= '
<div class="sg-drawer-item" data-id="' . (int) $item['id'] . '">
  <div style="width:48px;height:48px;flex-shrink:0;border-radius:2px;overflow:hidden;background:var(--dark-750)">' . $thumb . '</div>
  <div style="flex:1;min-width:0">
    <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . e($item['title']) . '</div>
    <div style="font-size:12px;color:var(--teal-300);margin-top:2px">' . $priceFormatted . '</div>
  </div>
  <button onclick="drawerRemove(' . (int) $item['id'] . ', this)" style="background:none;border:none;cursor:pointer;color:var(--white-faint);font-size:14px;padding:4px;transition:color .3s" onmouseenter="this.style.color=&apos;#e07070&apos;" onmouseleave="this.style.color=&apos;var(--white-faint)&apos;" title="Remove">✕</button>
</div>';
            }
        }

        return response()->json([
            'count' => count($cart),
            'total' => $this->settings->formatPrice($total),
            'html'  => $html,
        ]);
    }

    /* ---------------------------------------------------------------
     |  Helpers
     | --------------------------------------------------------------- */

    private function getCart(): array
    {
        return session(self::SESSION_KEY, []);
    }

    private function saveCart(array $cart): void
    {
        session([self::SESSION_KEY => $cart]);
    }

    private function cartTotal(array $cart): float
    {
        return array_sum(array_column($cart, 'subtotal'));
    }

    private function respond(Request $request, bool $success, string $message, int $count): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'count'   => $count,
                'total'   => $this->settings->formatPrice($this->cartTotal($this->getCart())),
            ]);
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        }
        return redirect()->back()->with('error', $message);
    }
}
