<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Product;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            ->with(['category', 'media']);

        // Filter by category (match on category code, case-insensitive)
        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('code', strtoupper($categorySlug));
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

        $product->load(['category', 'barcodes', 'media']);

        // Related products — same category, excluding current
        $relatedProducts = Product::websiteEnabled()
            ->active()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
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

    // ── Blog ────────────────────────────────────────────────────

    public function blogIndex(): View
    {
        $posts = Blog::published()->latest()->paginate(9);

        return view('website.blog.index', compact('posts'));
    }

    public function blogShow(Blog $blog): View
    {
        abort_if(! $blog->isActive(), 404);

        $relatedPosts = Blog::published()
            ->where('id', '!=', $blog->id)
            ->latest()
            ->limit(3)
            ->get();

        [$content, $tableOfContents] = $this->extractTableOfContents($blog->content);

        return view('website.blog.show', compact('blog', 'relatedPosts', 'content', 'tableOfContents'));
    }

    /**
     * Pulls every h2/h3 out of the post's (already-purified) content HTML,
     * stamps each with a unique #id, and returns the modified HTML alongside
     * a flat [{id, text, level}] outline for the post page's table-of-contents
     * jump links. Posts with no headings just get an empty outline back —
     * the view hides the TOC box in that case.
     *
     * @return array{0: string, 1: array<int, array{id: string, text: string, level: int}>}
     */
    private function extractTableOfContents(string $html): array
    {
        if (! str_contains($html, '<h2') && ! str_contains($html, '<h3')) {
            return [$html, []];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $toc  = [];
        $used = [];

        foreach ((new \DOMXPath($dom))->query('//h2 | //h3') as $heading) {
            $text = trim($heading->textContent);
            if ($text === '') {
                continue;
            }

            $slug = Str::slug($text) ?: 'section';
            $id   = $slug;
            for ($i = 2; in_array($id, $used, true); $i++) {
                $id = "{$slug}-{$i}";
            }
            $used[] = $id;

            $heading->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($heading->nodeName, 1)];
        }

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $newHtml = '';
        foreach ($wrapper->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }

        return [$newHtml, $toc];
    }

    // ── Static pages (About Us, Terms & Conditions, ...) ────────────

    public function pageShow(Page $page): View
    {
        return view('website.page', compact('page'));
    }

    // ── Contact ───────────────────────────────────────────────────

    public function contact(): View
    {
        return view('website.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:191'],
            'email'   => ['required', 'email', 'max:191'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create($data);

        // A mail-server hiccup should never turn an otherwise-successful
        // submission (already saved above) into a failed one for the
        // visitor — log and move on. No notification address configured
        // yet is the same "nothing to send to" case, not an error.
        $notifyEmail = app(SettingService::class)->get('contact_email');
        if ($notifyEmail) {
            try {
                Mail::to($notifyEmail)->send(new ContactFormMail($contactMessage));
            } catch (\Throwable $e) {
                logger()->error('Contact form notification email failed', [
                    'contact_message_id' => $contactMessage->id,
                    'message'            => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Thanks, {$contactMessage->name}! We've received your message and will be in touch soon.");
    }
}
