<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Barcode;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function __construct(
        protected BarcodeService $barcodeService,
    ) {
    }

    /* =================================================================
     |  Listing
     | ================================================================= */

    /**
     * Display the product listing page.
     * Renders dropdown options for filters (top-level categories, statuses).
     */
    public function index(): View
    {
        $topCategories = Category::active()->topLevel()->ordered()->get(['id', 'name']);
        return view('products.index', compact('topCategories'));
    }

    /**
     * Yajra DataTables AJAX endpoint.
     * Supports filters: category_id (top-level), subcategory_id (leaf),
     * status (0/1), website_enabled (0/1), featured (0/1), search.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category.parent', 'primaryBarcode']);

        // Filter: top-level category (matches the category's parent_id).
        if ($request->filled('category_id')) {
            $topId = (int) $request->input('category_id');
            $query->whereHas('category', function ($q) use ($topId) {
                $q->where('parent_id', $topId);
            });
        }

        // Filter: leaf subcategory direct match.
        if ($request->filled('subcategory_id')) {
            $query->where('category_id', (int) $request->input('subcategory_id'));
        }

        // Filter: product status.
        if ($request->filled('status') && in_array($request->input('status'), ['0', '1'], true)) {
            $query->where('status', (int) $request->input('status'));
        }

        // Filter: website enabled.
        if ($request->filled('website_enabled') && in_array($request->input('website_enabled'), ['0', '1'], true)) {
            $query->where('website_enabled', (int) $request->input('website_enabled'));
        }

        // Filter: featured products.
        if ($request->filled('featured') && in_array($request->input('featured'), ['0', '1'], true)) {
            $query->where('featured_product', (int) $request->input('featured'));
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function (Product $product) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $product->id . '" />';
            })
            ->editColumn('title', function (Product $product) {
                $thumb = $product->primary_thumb_url;
                $imgHtml = $thumb
                    ? '<img src="' . e($thumb) . '" alt="' . e($product->title) . '" class="img-fluid rounded" />'
                    : '<span class="d-inline-flex align-items-center justify-content-center bg-light text-muted rounded w-100 h-100"><i class="ti ti-photo"></i></span>';

                $subcategoryName = $product->category?->name ?? '—';

                return '
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">' . $imgHtml . '</div>
                        <div>
                            <h5 class="mb-1">
                                <a href="' . route('products.show', $product) . '" class="link-reset">'
                                    . e($product->title) .
                                '</a>
                            </h5>
                            <span class="text-muted fs-xs">' . e($subcategoryName) . '</span>
                        </div>
                    </div>
                ';
            })
            ->editColumn('sku', function (Product $product) {
                return '<code class="text-muted">' . e($product->sku) . '</code>';
            })
            ->addColumn('primary_barcode', function (Product $product) {
                $bc = $product->primaryBarcode;
                if (! $bc) {
                    return '<span class="text-muted">—</span>';
                }
                return '<small><code>' . e($bc->barcode_value) . '</code><br>'
                    . '<span class="badge badge-soft-info">' . e($bc->barcode_format) . '</span></small>';
            })
            ->addColumn('status_badge', function (Product $product) {
                $class = $product->isActive() ? 'badge-soft-success' : 'badge-soft-warning';
                return '<span class="badge ' . $class . ' fs-xxs">' . $product->statusLabel() . '</span>';
            })
            ->addColumn('website_badge', function (Product $product) {
                $class = $product->isWebsiteEnabled() ? 'badge-soft-info' : 'badge-soft-secondary';
                $label = $product->websiteVisibilityLabel();
                $featured = $product->featured_product
                    ? ' <i class="ti ti-star-filled text-warning" title="Featured"></i>'
                    : '';
                return '<span class="badge ' . $class . ' fs-xxs">' . $label . '</span>' . $featured;
            })
            ->editColumn('updated_at', function (Product $product) {
                $dt = $product->updated_at;
                if (! $dt) {
                    return '—';
                }
                return $dt->format('d M, Y') . ' <small class="text-muted">' . $dt->format('h:i A') . '</small>';
            })
            ->addColumn('action', function (Product $product) {
                $show           = route('products.show', $product);
                $edit           = route('products.edit', $product);
                $toggleStatus   = route('products.toggle-status', $product);
                $toggleWebsite  = route('products.toggle-website', $product);
                $destroy        = route('products.destroy', $product);

                $statusIcon  = $product->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';
                $websiteIcon = $product->isWebsiteEnabled() ? 'ti-world' : 'ti-world-off';

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-toggle-status"
                            data-url="' . $toggleStatus . '" title="Toggle Status">
                            <i class="ti ' . $statusIcon . ' fs-lg"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-toggle-website"
                            data-url="' . $toggleWebsite . '" title="Toggle Website">
                            <i class="ti ' . $websiteIcon . ' fs-lg"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                            data-url="' . $destroy . '" data-name="' . e($product->title) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['checkbox', 'title', 'sku', 'primary_barcode', 'status_badge', 'website_badge', 'updated_at', 'action'])
            ->make(true);
    }

    /* =================================================================
     |  Create / Store
     | ================================================================= */

    public function create(): View
    {
        return view('products.create', [
            'topCategories' => Category::active()->topLevel()->ordered()->get(['id', 'name', 'code']),
            'channels'      => Channel::active()->ordered()->get(['id', 'name', 'code']),
            'product'       => null,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data) {
            // 1. Create the product row.
            $product = Product::create($this->productPayload($data));

            // 2. Attach media collections.
            $this->syncMedia($product, $request, false);

            // 3. Create barcode rows + channel pivots.
            $this->syncBarcodes($product, $data['barcodes']);

            return $product;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Product created successfully.',
                'redirect' => route('products.index'),
                'data'     => $product->fresh(),
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    /* =================================================================
     |  Show / Edit / Update
     | ================================================================= */

    public function show(Product $product): View
    {
        $product->load([
            'category.parent',
            'barcodes.channels',
            'creator',
            'updater',
        ]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load([
            'category.parent',
            'barcodes.channels',
        ]);

        // For the subcategory cascading dropdown: provide the matching top
        // category id so the front-end can preselect it.
        $topCategoryId = $product->category?->parent_id ?? $product->category_id;

        $subcategories = Category::active()
            ->where('parent_id', $product->category?->parent_id)
            ->ordered()
            ->get(['id', 'name']);

        return view('products.edit', [
            'product'         => $product,
            'topCategories'   => Category::active()->topLevel()->ordered()->get(['id', 'name', 'code']),
            'channels'        => Channel::active()->ordered()->get(['id', 'name', 'code']),
            'topCategoryId'   => $topCategoryId,
            'subcategories'   => $subcategories,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $product, $data) {
            // 1. Update product columns.
            $product->fill($this->productPayload($data))->save();

            // 2. Sync media (handles removals + additions).
            $this->syncMedia($product, $request, true);

            // 3. Diff + sync barcodes.
            $this->syncBarcodes($product, $data['barcodes']);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Product updated successfully.',
                'redirect' => route('products.index'),
                'data'     => $product->fresh(),
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /* =================================================================
     |  Destroy
     | ================================================================= */

    public function destroy(Product $product, Request $request): JsonResponse|RedirectResponse
    {
        $product->delete(); // soft delete — barcodes cascade via FK

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /* =================================================================
     |  Toggle status / website
     | ================================================================= */

    public function toggleStatus(Product $product): JsonResponse
    {
        $product->status = ! $product->status;
        $product->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $product->status,
            'label'   => $product->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }

    /**
     * Toggle website_enabled. Business rule (spec §6): a product must have
     * a Primary Image before it can be enabled for the website.
     */
    public function toggleWebsite(Product $product): JsonResponse
    {
        // Trying to ENABLE — require a primary image.
        if (! $product->website_enabled && ! $product->hasPrimaryImage()) {
            return response()->json([
                'success' => false,
                'message' => 'A Primary Image is required before enabling website visibility.',
            ], 422);
        }

        $product->website_enabled = ! $product->website_enabled;
        $product->save(); // booted() handles enabled_at / disabled_at + featured-flag clearing

        return response()->json([
            'success' => true,
            'enabled' => (bool) $product->website_enabled,
            'label'   => $product->websiteVisibilityLabel(),
            'message' => 'Website visibility updated.',
        ]);
    }

    /**
     * Bulk enable/disable website visibility for up to 500 products
     * (spec §6.5 caps the operation at 500 per request).
     */
    public function bulkWebsiteToggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:500'],
            'ids.*'  => ['integer', 'exists:products,id'],
            'action' => ['required', 'string', 'in:enable,disable'],
        ]);

        $ids    = $validated['ids'];
        $enable = $validated['action'] === 'enable';

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($ids, $enable, &$updated, &$skipped) {
            // We have to iterate (not a single mass update) so booted()
            // event hooks fire — they manage enabled_at/disabled_at
            // timestamps and clear featured_product on disable.
            $products = Product::whereIn('id', $ids)->get();

            foreach ($products as $product) {
                if ($enable) {
                    if (! $product->hasPrimaryImage()) {
                        $skipped++;
                        continue;
                    }
                    if (! $product->website_enabled) {
                        $product->website_enabled = true;
                        $product->save();
                        $updated++;
                    }
                } else {
                    if ($product->website_enabled) {
                        $product->website_enabled = false;
                        $product->save();
                        $updated++;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'skipped' => $skipped,
            'message' => $enable
                ? "{$updated} product(s) enabled for website" . ($skipped ? ", {$skipped} skipped (missing primary image)" : '') . '.'
                : "{$updated} product(s) disabled from website.",
        ]);
    }

    /* =================================================================
     |  AJAX helpers — cascading dropdown + barcode utilities
     | ================================================================= */

    /**
     * Returns active subcategories under a given top-level category, for
     * the cascading Category → Subcategory dropdown on the product form.
     */
    public function subcategoriesByParent(Category $category): JsonResponse
    {
        $subcategories = Category::active()
            ->where('parent_id', $category->id)
            ->ordered()
            ->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data'    => $subcategories,
        ]);
    }

    /**
     * Returns a freshly-generated unique EAN-13.
     */
    public function generateBarcode(): JsonResponse
    {
        try {
            $value = $this->barcodeService->generateUniqueEan13();
            return response()->json([
                'success' => true,
                'value'   => $value,
                'format'  => Barcode::FORMAT_EAN_13,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate a unique barcode. Please try again.',
            ], 500);
        }
    }

    /**
     * Live-validates a barcode value against its format + DB uniqueness.
     * Used by the multi-barcode panel as the user types.
     */
    public function validateBarcode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value'      => ['required', 'string', 'max:100'],
            'format'     => ['required', 'string'],
            'ignore_id'  => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
        ]);

        // Format-specific check (length, charset, check digit).
        $err = $this->barcodeService->validateFormat($validated['value'], $validated['format']);
        if ($err !== null) {
            return response()->json(['success' => false, 'message' => $err]);
        }

        // Uniqueness — ignore the row's own id when editing, AND ignore any
        // barcode belonging to the same product (so value-swapping is OK).
        $ignoreId = $validated['ignore_id'] ?? null;

        $duplicate = Barcode::withTrashed()
            ->where('barcode_value', $validated['value'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->first();

        if ($duplicate) {
            $sameProduct = isset($validated['product_id'])
                && $duplicate->product_id === (int) $validated['product_id'];

            if (! $sameProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barcode is already assigned to another product.',
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Barcode is valid.']);
    }

    /* =================================================================
     |  Private helpers
     | ================================================================= */

    /**
     * Build the column payload for Product::create() / fill().
     * Filters validated data down to schema columns and normalises blanks.
     */
    protected function productPayload(array $data): array
    {
        return [
            // Core
            'title'             => $data['title'],
            'sku'               => $data['sku'],
            'category_id'       => $data['category_id'],
            'short_description' => $data['short_description'] ?? null,
            'full_description'  => $data['full_description'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'notes_tags'        => $data['notes_tags'] ?? null,
            'status'            => (bool) ($data['status'] ?? false),

            // Gemstone
            'carat_weight'       => $data['carat_weight'] ?? null,
            'stone_type'         => $data['stone_type'] ?? null,
            'colour_grade'       => $data['colour_grade'] ?? null,
            'clarity_grade'      => $data['clarity_grade'] ?? null,
            'cut_shape'          => $data['cut_shape'] ?? null,
            'treatment'          => $data['treatment'] ?? null,
            'certificate_number' => $data['certificate_number'] ?? null,

            // Website
            'website_enabled'     => (bool) ($data['website_enabled'] ?? false),
            'website_price'       => $data['website_price']       ?? null,
            'website_title'       => $data['website_title']       ?? null,
            'website_description' => $data['website_description'] ?? null,
            'featured_product'    => (bool) ($data['featured_product'] ?? false),
            'website_sort_order'  => $data['website_sort_order']  ?? null,
        ];
    }

    /**
     * Attach uploaded media to the product, handling create vs update logic.
     */
    protected function syncMedia(Product $product, Request $request, bool $isUpdate): void
    {
        /* ----- Primary image ----- */
        if ($isUpdate && $request->boolean('remove_primary_image')) {
            $product->clearMediaCollection(Product::MEDIA_COLLECTION_PRIMARY);
        }
        if ($request->hasFile('primary_image')) {
            $product->clearMediaCollection(Product::MEDIA_COLLECTION_PRIMARY);
            $product->addMediaFromRequest('primary_image')
                ->toMediaCollection(Product::MEDIA_COLLECTION_PRIMARY);
        }

        /* ----- Gallery images ----- */
        if ($isUpdate && is_array($request->input('remove_gallery_ids'))) {
            $removeIds = array_map('intval', $request->input('remove_gallery_ids'));
            $product->media()
                ->where('collection_name', Product::MEDIA_COLLECTION_GALLERY)
                ->whereIn('id', $removeIds)
                ->get()
                ->each
                ->delete();
        }
        if ($request->hasFile('gallery_images')) {
            $existing = $product->getMedia(Product::MEDIA_COLLECTION_GALLERY)->count();
            $slotsLeft = Product::MAX_GALLERY_IMAGES - $existing;

            foreach ($request->file('gallery_images') as $file) {
                if ($slotsLeft <= 0) {
                    break;
                }
                $product->addMedia($file)->toMediaCollection(Product::MEDIA_COLLECTION_GALLERY);
                $slotsLeft--;
            }
        }

        /* ----- Certificate (image or PDF) ----- */
        if ($isUpdate && $request->boolean('remove_certificate_image')) {
            $product->clearMediaCollection(Product::MEDIA_COLLECTION_CERTIFICATE);
        }
        if ($request->hasFile('certificate_image')) {
            $product->clearMediaCollection(Product::MEDIA_COLLECTION_CERTIFICATE);
            $product->addMediaFromRequest('certificate_image')
                ->toMediaCollection(Product::MEDIA_COLLECTION_CERTIFICATE);
        }
    }

    /**
     * Diff-sync barcodes for a product.
     *  - existing rows present in payload (matched by id) → update
     *  - rows in payload without id → create
     *  - existing rows NOT in payload → delete
     *  - each row's channel_ids → sync the pivot
     */
    protected function syncBarcodes(Product $product, array $barcodes): void
    {
        // Pre-load existing barcode IDs for diff.
        $existingIds = $product->barcodes()->pluck('id')->all();
        $submittedIds = [];

        foreach ($barcodes as $index => $row) {
            $id          = isset($row['id']) ? (int) $row['id'] : null;
            $payload = [
                'product_id'      => $product->id,
                'barcode_value'   => trim((string) ($row['value'] ?? '')),
                'barcode_format'  => (string) ($row['format'] ?? ''),
                'barcode_label'   => $row['label'] ?? null,
                'is_primary'      => (bool) ($row['is_primary'] ?? false),
                'sequence_number' => $index + 1,
            ];

            if ($id && in_array($id, $existingIds, true)) {
                // Update existing row.
                $barcode = Barcode::find($id);
                if ($barcode) {
                    $barcode->fill($payload)->save();
                    $submittedIds[] = $barcode->id;
                }
            } else {
                // Create new row.
                $barcode = Barcode::create($payload);
                $submittedIds[] = $barcode->id;
            }

            // Sync channel pivot for this barcode.
            $channelIds = $row['channel_ids'] ?? [];
            if (! is_array($channelIds)) {
                $channelIds = [];
            }
            $barcode->channels()->sync(array_map('intval', $channelIds));
        }

        // Delete rows that were removed from the payload.
        $toDelete = array_diff($existingIds, $submittedIds);
        if (! empty($toDelete)) {
            Barcode::whereIn('id', $toDelete)->delete();
        }
    }
}
