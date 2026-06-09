<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public-facing storefront for Sukaina Gems.
 *
 * All data pulled directly from the existing ERP models:
 *  - Products with website_enabled = true
 *  - Categories with is_gemstone = true
 *  - Live Banners (active + within date window)
 *
 * No auth required — these routes are public.
 */
class WebsiteController extends Controller
{
    // ── Homepage ─────────────────────────────────────────────────────

    public function home(): View
    {
        // Hero banners (position = home, live)
        $heroBanners = Banner::live()
            ->forPosition('home')
            ->ordered()
            ->limit(5)
            ->get();

        // Gemstone categories for "Shop by Collection" strip
        $categories = Category::where('is_gemstone', true)
            ->whereNull('parent_id')          // top-level only
            ->withCount(['products' => fn ($q) => $q->where('website_enabled', true)->where('status', 1)])
            ->orderBy('display_order')
            ->limit(6)
            ->get();

        // Featured products — website_enabled + featured_product flag, active
        $featuredProducts = Product::websiteEnabled()
            ->active()
            ->featured()
            ->with(['category', 'primaryBarcode', 'media'])
            ->orderBy('website_sort_order')
            ->limit(8)
            ->get();

        // Latest arrivals — most recently website-enabled
        $latestProducts = Product::websiteEnabled()
            ->active()
            ->with(['category', 'media'])
            ->orderByDesc('website_enabled_at')
            ->limit(4)
            ->get();

        // Total live gems count for the hero stat
        $totalGems = Product::websiteEnabled()->active()->count();

        return view('website.home', compact(
            'heroBanners',
            'categories',
            'featuredProducts',
            'latestProducts',
            'totalGems',
        ));
    }

    // ── Collections / Shop ───────────────────────────────────────────

    public function collections(Request $request): View
    {
        $categorySlug = $request->get('category');
        $sort         = $request->get('sort', 'featured');
        $search       = $request->get('q');

        // Build base query
        $query = Product::websiteEnabled()
            ->active()
            ->with(['category.parent', 'media']);

        // Filter by category (match on category code, case-insensitive)
        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $code = strtoupper($categorySlug);
                $q->where('code', $code)
                  ->orWhereHas('parent', fn ($p) => $p->where('code', $code));
            });
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('stone_type', 'like', "%{$search}%")
                  ->orWhere('country_of_origin', 'like', "%{$search}%")
                  ->orWhere('website_title', 'like', "%{$search}%");
            });
        }

        // Sorting
        $query = match ($sort) {
            'price_asc'  => $query->orderBy('website_price', 'asc'),
            'price_desc' => $query->orderBy('website_price', 'desc'),
            'latest'     => $query->orderByDesc('website_enabled_at'),
            'carat_desc' => $query->orderByDesc('carat_weight'),
            default      => $query->orderBy('website_sort_order')->orderByDesc('featured_product'),
        };

        $products = $query->paginate(12)->withQueryString();

        // Sidebar categories
        $categories = Category::where('is_gemstone', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->where('website_enabled', true)->where('status', 1)])
            ->orderBy('display_order')
            ->get();

        // Promo banners
        $promoBanner = Banner::live()->forPosition('promo')->ordered()->first();

        return view('website.collections', compact(
            'products',
            'categories',
            'categorySlug',
            'sort',
            'search',
            'promoBanner',
        ));
    }

    // ── Product Detail ───────────────────────────────────────────────

    public function product(Product $product): View
    {
        // 404 if not visible on website
        abort_if(! $product->website_enabled || ! $product->status, 404);

        $product->load(['category.parent', 'barcodes', 'media']);

        // Related products — same top-level category, excluding current
        $relatedProducts = Product::websiteEnabled()
            ->active()
            ->where('id', '!=', $product->id)
            ->whereHas('category', function ($q) use ($product) {
                // Match same category OR same parent category
                $parentId = $product->category?->parent_id ?? $product->category_id;
                $q->where('parent_id', $parentId)
                  ->orWhere('id', $parentId);
            })
            ->with(['category', 'media'])
            ->limit(4)
            ->get();

        // Product-page banners
        $productBanner = Banner::live()->forPosition('product')->ordered()->first();

        return view('website.product', compact(
            'product',
            'relatedProducts',
            'productBanner',
        ));
    }
}
