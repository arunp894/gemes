<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\Packing;
use App\Models\PackingSource;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Repositories\PackingRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Packing orchestration -- the only place a sellable Product gets
 * created now (see PurchaseService, which stopped creating them). A
 * packing draws a chosen qty from one or more raw (product_id null)
 * purchase_products rows; every selected source automatically becomes
 * its own new Product, one-to-one, with its own new PurchaseProduct
 * output row -- see syncOutputsFromSources(). There's no separate
 * output-curation step: category, gemstone descriptors, and cost all
 * come straight off the source's purchase line/row. "Show on website"
 * and Selling Price ARE curated, per source row (see
 * PackingSource::$website_enabled/$website_price) -- everything else
 * about the product a source creates is not.
 *
 * Draft packings create their Products/Barcodes/output rows immediately
 * (so staff can attach photos on the Products screen before posting --
 * same reasoning PurchaseService used to apply to its own draft rows),
 * but don't touch the stock ledger. post() is what actually consumes
 * the raw sources and credits the new stock, via
 * StockService::recordPackingPosting().
 */
class PackingService
{
    public function __construct(
        private PackingRepository $repo,
        private StockService      $stock,
        private BarcodeService    $barcodes,
    ) {}

    /* ─── Public API ───────────────────────────────────────── */

    /**
     * Expected payload (see StorePackingRequest):
     * [
     *   'packing_date'    => 'YYYY-MM-DD',
     *   'location_id'     => int,
     *   'status'          => 'draft'|'posted',
     *   'note'            => string|null,
     *   'sources' => [
     *     [
     *       'purchase_product_id' => int,
     *       'qty_taken'           => int,
     *       'website_enabled'     => bool|null,   // per-row; defaults from the
     *                                              // piece's purchase line hint
     *       'website_price'       => float|null,  // per-row Selling Price; defaults
     *                                              // from the piece's own website_price
     *     ],
     *     ...
     *   ],
     * ]
     *
     * No 'outputs' key -- see syncOutputsFromSources().
     */
    public function create(array $data): Packing
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['packing_date'] ?? now()->toDateString());

            $intendedStatus = $data['status'] ?? Packing::STATUS_DRAFT;

            $packing = new Packing();
            $packing->packing_number = Packing::generatePackingNumber($date);
            $packing->packing_date   = $date->toDateString();
            $packing->location_id    = $data['location_id'];
            $packing->note           = $data['note'] ?? null;
            // Always build as draft first, then promote via post() so the
            // stock movements are actually written.
            $packing->status         = Packing::STATUS_DRAFT;
            $packing->save();

            $this->syncSources($packing, $data['sources'] ?? []);
            $this->syncOutputsFromSources($packing);

            if ($intendedStatus === Packing::STATUS_POSTED) {
                $this->post($packing);
            }

            return $this->repo->refresh($packing);
        });
    }

    public function update(Packing $packing, array $data): Packing
    {
        if (! $packing->isEditable()) {
            throw new InvalidArgumentException('Only draft packings can be edited.');
        }

        return DB::transaction(function () use ($packing, $data) {
            $packing->packing_date = $data['packing_date'] ?? $packing->packing_date;
            $packing->location_id  = $data['location_id']  ?? $packing->location_id;
            $packing->note         = $data['note']          ?? $packing->note;
            $packing->save();

            $this->syncSources($packing, $data['sources'] ?? []);
            $this->syncOutputsFromSources($packing);

            return $this->repo->refresh($packing);
        });
    }

    /**
     * Post a draft packing: creates the ledger movements (raw sources
     * consumed, new output rows credited) via StockService. The
     * Products/Barcodes/rows already exist from create()/update() --
     * this step is purely the stock-side commit.
     */
    public function post(Packing $packing): Packing
    {
        if ($packing->isPosted()) {
            return $packing;
        }
        if ($packing->isCancelled()) {
            throw new InvalidArgumentException('Cannot post a cancelled packing.');
        }
        if ($packing->outputs()->count() === 0) {
            throw new InvalidArgumentException('Cannot post an empty packing -- add at least one output.');
        }
        if ($packing->sources()->count() === 0) {
            throw new InvalidArgumentException('Cannot post a packing with no source stock selected.');
        }

        return DB::transaction(function () use ($packing) {
            $packing->status     = Packing::STATUS_POSTED;
            $packing->posted_at  = now();
            $packing->save();

            $this->stock->recordPackingPosting($packing);

            return $this->repo->refresh($packing);
        });
    }

    /**
     * Cancel a packing. Draft: just deletes it (nothing was ever booked
     * to the ledger). Posted: reverses the stock movements first via
     * StockService::reversePackingPosting(), which refuses if any output
     * has already sold or moved on.
     */
    public function cancel(Packing $packing): Packing
    {
        if ($packing->isCancelled()) {
            return $packing;
        }

        return DB::transaction(function () use ($packing) {
            if ($packing->isPosted()) {
                $this->stock->reversePackingPosting($packing);
            }

            $packing->status       = Packing::STATUS_CANCELLED;
            $packing->cancelled_at = now();
            $packing->save();

            return $this->repo->refresh($packing);
        });
    }

    public function delete(Packing $packing): void
    {
        if (! $packing->isDraft()) {
            throw new InvalidArgumentException('Only draft packings can be deleted -- cancel a posted one instead.');
        }

        DB::transaction(function () use ($packing) {
            foreach ($packing->outputs as $row) {
                $this->retireOutput($row);
            }
            $packing->sources()->each(fn (PackingSource $s) => $s->delete());
            $packing->delete();
        });
    }

    /* ─── Internal helpers ─────────────────────────────────── */

    /**
     * Diff-sync the raw pieces this packing draws on. Unlike
     * PurchaseService::syncLines(), a source row has no downstream
     * identity of its own to preserve -- it's just a qty claim -- so a
     * draft's sources are simply wiped and rebuilt on every save.
     */
    private function syncSources(Packing $packing, array $sources): void
    {
        $packing->sources()->each(fn (PackingSource $s) => $s->delete());

        foreach ($sources as $row) {
            $ppId = (int) ($row['purchase_product_id'] ?? 0);
            $qty  = max(0, (int) ($row['qty_taken'] ?? 0));
            if ($ppId <= 0 || $qty <= 0) {
                continue;
            }

            $piece = PurchaseProduct::find($ppId);
            if (! $piece) {
                throw new InvalidArgumentException("Piece #{$ppId} not found.");
            }
            if ($piece->product_id) {
                throw new InvalidArgumentException(
                    "Piece #{$ppId} (lot {$piece->lot_code}) is already a packed/retail item, not raw stock."
                );
            }

            // Selling Price defaults straight off the raw piece's own
            // value (itself seeded from the purchase line at purchase-
            // entry time -- see purchase_products.website_price) when the
            // row doesn't send its own override. "Show on website"
            // defaults off the purchase line's own hint the same way.
            // Same fallback shape PurchaseService already uses for
            // carat_weight / website_price at the line level.
            $websitePrice = isset($row['website_price']) && $row['website_price'] !== ''
                ? (float) $row['website_price']
                : ($piece->website_price !== null ? (float) $piece->website_price : null);

            $websiteEnabled = array_key_exists('website_enabled', $row) && $row['website_enabled'] !== null
                ? (bool) $row['website_enabled']
                : (bool) ($piece->line?->website_enabled ?? false);

            $packing->sources()->save(new PackingSource([
                'purchase_product_id' => $ppId,
                'qty_taken'           => $qty,
                'website_enabled'     => $websiteEnabled,
                'website_price'       => $websitePrice,
            ]));
        }
    }

    /**
     * Build this packing's output rows straight from its sources -- one
     * new sellable Product per raw piece selected above, no manual
     * curation. Category, gemstone descriptors, and cost all come
     * straight off the source's purchase line/row; "show on website"
     * and Selling Price come off the source's OWN per-row fields (see
     * PackingSource -- syncSources() already resolved their defaults
     * when the source was saved, so this just reads them back).
     *
     * Like syncSources(), existing outputs are simply retired and
     * rebuilt on every save rather than diffed/matched by id -- same
     * trade-off PurchaseService already accepts for its own rows (lot
     * codes/products regenerate whenever a purchase is edited, by
     * design). Only reachable while the packing is still a draft (see
     * update()'s guard), so nothing has been posted to the stock ledger
     * yet -- there's never a movement to reconcile here, just the row
     * and the product it created.
     */
    private function syncOutputsFromSources(Packing $packing): void
    {
        foreach ($packing->outputs as $row) {
            $this->retireOutput($row);
        }

        $sources = $packing->sources()->with('purchaseProduct.line.category')->get();

        foreach ($sources as $source) {
            $piece    = $source->purchaseProduct;
            $line     = $piece?->line;
            $category = $line?->category;

            if (! $piece || ! $category) {
                throw new InvalidArgumentException(
                    "Source piece #{$source->purchase_product_id} is missing its category -- cannot create a product."
                );
            }

            $qty            = max(1, (int) $source->qty_taken);
            $title          = trim((string) $line->title) !== '' ? $line->title : $category->name;
            $websiteEnabled = (bool) $source->website_enabled;

            $product = Product::create([
                'title'            => $title,
                'sku'              => Product::generateSku($category),
                'category_id'      => $category->id,
                // Storefront listing needs BOTH website_enabled and an
                // Active status (see WebsiteController) -- same rule
                // PurchaseService used to apply at creation time.
                'status'           => $websiteEnabled ? Product::STATUS_ACTIVE : Product::STATUS_DRAFT,
                'pack_type'        => Product::PACK_TYPE_PIECE,
                'carat_weight'     => $piece->carat_weight,
                'stone_type'       => $line->stone_type,
                'colour_grade'     => $line->colour_grade,
                'clarity_grade'    => $line->clarity_grade,
                'cut_shape'        => $line->cut_shape,
                'treatment'        => $line->treatment,
                'website_enabled'  => $websiteEnabled,
                // Selling Price -- this source's own value, resolved by
                // syncSources() (defaults from the raw piece's own
                // website_price when the form didn't override it).
                'website_price'    => $source->website_price,
                'featured_product' => false,
            ]);

            Barcode::create([
                'product_id'      => $product->id,
                'barcode_value'   => $this->barcodes->generateUniqueEan13(),
                'barcode_format'  => Barcode::FORMAT_EAN_13,
                'barcode_label'   => null,
                'is_primary'      => true,
                'sequence_number' => 1,
            ]);

            $row = new PurchaseProduct([
                'packing_id'       => $packing->id,
                'product_id'       => $product->id,
                'qty'              => $qty,
                'carat_weight'     => $piece->carat_weight,
                'lot_code'         => PurchaseProduct::generatePackingLotCode(),
                'rack_id'          => $piece->rack_id,
                // Cost basis carried straight over from what was
                // actually paid for this raw material.
                'price'            => $piece->price,
                // Selling Price -- same value just stamped onto the
                // Product above, kept here too so the output row's own
                // record matches what purchase_products.website_price
                // already does for raw rows.
                'website_price'    => $source->website_price,
                'tax_percent'      => 0,
                'tax_amount'       => 0,
                'discount_percent' => 0,
                'discount_amount'  => 0,
            ]);
            $row->save();
        }
    }

    /**
     * Remove an output row that's no longer in the payload. Only
     * reachable while the packing is still a draft -- post() locks the
     * outputs in by writing ledger movements against them (and update()
     * refuses to run at all once posted), so there's never stock to
     * reverse here, just the row and the product it created.
     */
    private function retireOutput(PurchaseProduct $row): void
    {
        $product = $row->product_id ? Product::find($row->product_id) : null;
        $row->delete();
        if ($product) {
            $product->delete();
        }
    }
}
