<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePayment;
use App\Models\PurchaseProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Repositories\PurchaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Purchase orchestration. The controller hands off the whole request
 * payload here; this class owns:
 *
 *   - transactional save (purchase + lines + inventory rows + products)
 *   - invoice number generation (per-supplier-per-month sequence)
 *   - line/row total reconciliation (server is source of truth)
 *   - status transitions (draft -> posted -> cancelled)
 *
 * Stock is NOT a separate table — purchase_products IS the stock ledger.
 * Posting only flips Purchase::status, which lets stock queries filter
 * via `whereHas('line.purchase', fn($q) => $q->posted())`.
 *
 * As of the "purchase creates its own products" change: a purchase line
 * no longer points at a pre-existing catalogue product. It's a template
 * (title, category, gemstone fields) that gets stamped onto a brand-new
 * Product for every inventory row generated under it — one row is
 * always exactly one product. Box lines generate one row per box (Pack
 * Qty drives the count); Piece lines always generate exactly one row,
 * with the physical count captured on that row's own qty field instead
 * so several identical pieces can share a single product. See
 * syncLines().
 */
class PurchaseService
{
    public function __construct(
        private PurchaseRepository $repo,
        private StockService       $stock,
        private BarcodeService     $barcodes,
    ) {}

    /* ─── Public API ───────────────────────────────────────── */

    /**
     * Create a new purchase from validated request data.
     *
     * Expected payload shape (see StorePurchaseRequest):
     * [
     *   'supplier_id'    => int,
     *   'purchase_date'  => 'YYYY-MM-DD',
     *   'tax_type'       => 'none'|'cgst_sgst'|'igst',
     *   'note'           => string|null,
     *   'status'         => 'draft'|'posted',
     *   'payments'       => [                          // optional; multiple rows like SaleService
     *     ['payment_date' => 'YYYY-MM-DD', 'amount' => float, 'payment_method' => string, 'reference_number' => string|null],
     *     ...
     *   ],
     *   'lines' => [
     *     [
     *       'category_id'        => int,
     *       'title'              => string,
     *       'short_description'  => string|null,
     *       'full_description'   => string|null,
     *       'country_of_origin_id' => int|null,
     *       'notes_tags'         => string|null,
     *       'website_price'      => float|null,   // seeds the created product(s)' listing price
     *       'carat_weight'       => float|null,   // line-level default
     *       'stone_type'         => string|null,
     *       'colour_grade'       => string|null,
     *       'clarity_grade'      => string|null,
     *       'cut_shape'          => string|null,
     *       'treatment'          => string|null,
     *       'type'               => 'piece'|'box',
     *       'package_name'       => string|null,
     *       'package_qty'        => int,          // row count for 'box'; forced to 1 for 'piece'
     *       'remarks'            => string|null,
     *       'rows' => [
     *         [
     *           'qty'              => int,
     *           'carat_weight'     => float|null,  // per-row override
     *           'barcode'          => string|null, // receiving-dock scan, not the product's retail barcode
     *           'rack_id'          => int|null,
     *           'serial_number'    => string|null,
     *           'price'            => float,
     *           'expiry_date'      => 'YYYY-MM-DD'|null,
     *           'manufacture_date' => 'YYYY-MM-DD'|null,
     *           'remarks'          => string|null,
     *         ],
     *         ...
     *       ],
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $date     = Carbon::parse($data['purchase_date']);

            $intendedStatus = $data['status'] ?? Purchase::STATUS_DRAFT;

            $purchase = new Purchase();
            $purchase->supplier_id    = $supplier->id;
            $purchase->purchase_date  = $date->toDateString();
            $purchase->invoice_number = Purchase::generateInvoiceNumber($supplier, $date);
            $purchase->location_id    = $data['location_id'] ?? null;
            $purchase->tax_type       = $data['tax_type'] ?? Purchase::TAX_NONE;
            $purchase->note           = $data['note'] ?? null;
            // Always build as DRAFT first, then promote via post() so the
            // stock IN movements are written. Setting status='posted'
            // directly here would leave the ledger empty.
            $purchase->status         = Purchase::STATUS_DRAFT;
            $purchase->save();

            $this->syncLines($purchase, $data['lines'] ?? []);
            $this->syncPayments($purchase, $data['payments'] ?? [], replace: true);
            $this->recalculate($purchase);

            if ($intendedStatus === Purchase::STATUS_POSTED) {
                $this->post($purchase);
            }

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Update an existing purchase. Behaviour depends on status:
     *
     *   - Draft:   lines/rows are diff-synced in place (see syncLines()).
     *   - Posted:  same diff-sync, but the inventory ledger must be kept
     *              in sync too — see updatePostedLines(). Only reachable
     *              when Purchase::editBlockReason() is null (no sales
     *              against this purchase's stock yet, and within the
     *              configurable edit window).
     *   - Other (cancelled): lightweight note-only update, as a
     *              defensive fallback — the controller's editBlockReason()
     *              gate normally prevents reaching here at all. Payments
     *              are never touched by this method; they're managed via
     *              addPayment()/removePayment() from the show page.
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {

            if ($purchase->isPosted()) {
                return $this->updatePostedLines($purchase, $data);
            }

            if (! $purchase->isDraft()) {
                $purchase->note = $data['note'] ?? $purchase->note;
                $purchase->save();
                $this->recalculate($purchase);
                return $this->repo->refresh($purchase);
            }

            $purchase->purchase_date = $data['purchase_date'] ?? $purchase->purchase_date;
            $purchase->location_id   = $data['location_id']   ?? $purchase->location_id;
            $purchase->tax_type      = $data['tax_type']      ?? $purchase->tax_type;
            $purchase->note          = $data['note']          ?? $purchase->note;
            $purchase->save();

            // NOTE: this used to hard-delete every line/row before
            // rebuilding from scratch. It can't anymore — a draft's rows
            // may already have created products that picked up photos or
            // a description while the invoice itself was still being
            // finalised. syncLines() diffs instead of wiping.
            $this->syncLines($purchase, $data['lines'] ?? []);
            // Only touch payments if the caller actually sent them, so
            // the edit form (which omits payments) never wipes existing
            // ones — mirrors SaleService::updateDraftLines().
            if (array_key_exists('payments', $data)) {
                $this->syncPayments($purchase, $data['payments'], replace: true);
            }
            $this->recalculate($purchase);

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Edit a POSTED purchase's lines while keeping the stock ledger in
     * sync. Only reachable when editBlockReason() is null, which already
     * guarantees no sale has consumed any of this purchase's stock — so
     * every currently-posted piece's on-hand balance equals exactly its
     * original IN quantity (modulo transfers, checked below).
     *
     * Strategy (ledger is append-only, ON DELETE RESTRICT on
     * stock_movements.purchase_product_id, so old rows can't be hard
     * deleted):
     *   1. Verify none of the currently-posted pieces have moved away
     *      from the purchase's location (e.g. via a stock transfer).
     *   2. Emit OUT "purchase_cancel" movements reversing every current
     *      piece at the OLD location.
     *   3. Update header fields and diff-sync lines/rows from the new
     *      payload via syncLines() + recalculate() — rows the client
     *      echoes back by id keep their product; only genuinely new or
     *      removed rows touch a product record.
     *   4. Emit fresh IN "purchase" movements for the current rows at
     *      the (possibly updated) location.
     */
    private function updatePostedLines(Purchase $purchase, array $data): Purchase
    {
        $oldLocationId = (int) $purchase->location_id;
        if (! $oldLocationId) {
            throw new InvalidArgumentException('Cannot edit this purchase: it has no location set.');
        }

        $purchase->load('lines.rows');

        // 1. Pre-flight — none of this purchase's pieces may have moved
        //    away from the posting location (e.g. a stock transfer).
        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $onHand = $this->stock->onHandForPiece($row->id, $oldLocationId);
                if ($onHand < $qty) {
                    throw new InvalidArgumentException(
                        "Cannot edit this purchase: stock for one of its items has already moved "
                        . "(on hand {$onHand}, expected {$qty}). Reverse the downstream movement first."
                    );
                }
            }
        }

        // 2. Reverse every current piece at the old location. Reads each
        //    row's OWN product_id — every row is its own product now, the
        //    line no longer carries one shared product_id.
        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $this->stock->record([
                    'purchase_product_id' => $row->id,
                    'product_id'          => $row->product_id,
                    'location_id'         => $oldLocationId,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => $qty,
                    'reason'              => StockMovement::REASON_PURCHASE_CANCEL,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $row->rack_id,
                    'notes'               => 'Reversed for edit of purchase ' . $purchase->invoice_number,
                ]);
            }
        }

        // 3. Header fields + diff-sync lines/rows from the new payload.
        //    paid_amount is intentionally left untouched here — it's
        //    derived from purchase_payments now (see recalculatePayments()),
        //    managed from the purchase's detail page, not this form.
        $purchase->purchase_date = $data['purchase_date'] ?? $purchase->purchase_date;
        $purchase->location_id   = $data['location_id']   ?? $purchase->location_id;
        $purchase->tax_type      = $data['tax_type']      ?? $purchase->tax_type;
        $purchase->note          = $data['note']          ?? $purchase->note;
        $purchase->save();

        $this->syncLines($purchase, $data['lines'] ?? []);
        $this->recalculate($purchase);

        // 4. Post fresh IN movements for the current rows at the
        //    (possibly updated) location.
        $newLocationId = (int) ($purchase->location_id ?: $oldLocationId);
        $purchase->load('lines.rows');

        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $this->stock->record([
                    'purchase_product_id' => $row->id,
                    'product_id'          => $row->product_id,
                    'location_id'         => $newLocationId,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $qty,
                    'reason'              => StockMovement::REASON_PURCHASE,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $row->rack_id,
                    'movement_date'       => optional($purchase->purchase_date)->toDateString() ?? now()->toDateString(),
                ]);
            }
        }

        return $this->repo->refresh($purchase);
    }

    /**
     * Post a draft purchase. Once posted the inventory rows are live.
     *
     * Wires into StockService: emits IN movements per purchase_product
     * row at the purchase's location. The movement creation runs in the
     * SAME transaction as the status flip so a failure rolls everything
     * back cleanly.
     */
    public function post(Purchase $purchase): Purchase
    {
        if ($purchase->isPosted()) {
            return $purchase;
        }
        if ($purchase->isCancelled()) {
            throw new InvalidArgumentException('Cannot post a cancelled purchase.');
        }
        if ($purchase->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot post an empty purchase.');
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->status = Purchase::STATUS_POSTED;
            $purchase->save();

            // Emit IN movements. StockService guards against double-post
            // via hasMovementsFromSource() so a retry is safe.
            $this->stock->recordPurchasePosting($purchase);

            return $this->repo->refresh($purchase);
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            // If the purchase was posted, reverse its stock movements
            // BEFORE flipping status — that way the safety guard in
            // StockService::reversePurchasePosting() can still see the
            // 'posted' history when checking downstream consumption.
            if ($purchase->isPosted()) {
                $this->stock->reversePurchasePosting($purchase);
            }

            $purchase->status = Purchase::STATUS_CANCELLED;
            $purchase->save();

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Delete a purchase entirely. Retires every row it created via the
     * same safety check as removing a single row on edit (see
     * retireRow()) — a purchase whose products have already picked up
     * photos or a website listing won't be silently deleted out from
     * under them; it throws instead, so the caller can unlink those
     * products first if that's really what's wanted.
     */
    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase->lines()->each(function (PurchaseLine $l) {
                foreach ($l->rows()->get() as $row) {
                    $this->retireRow($row);
                }
                $l->delete();
            });
            $purchase->payments()->each(fn (PurchasePayment $p) => $p->delete());
            $purchase->delete();
        });
    }

    /* ─── Payment helpers (public for the show page) ─────── */

    public function addPayment(Purchase $purchase, array $data): PurchasePayment
    {
        return DB::transaction(function () use ($purchase, $data) {
            $payment = new PurchasePayment([
                'payment_date'     => $data['payment_date']     ?? now()->toDateString(),
                'amount'           => (float) ($data['amount']  ?? 0),
                'payment_method'   => $data['payment_method']   ?? PurchasePayment::METHOD_CASH,
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes']            ?? null,
            ]);
            $purchase->payments()->save($payment);
            $this->recalculatePayments($purchase);
            return $payment->refresh();
        });
    }

    public function removePayment(PurchasePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $purchase = $payment->purchase;
            $payment->delete();
            if ($purchase) {
                $this->recalculatePayments($purchase);
            }
        });
    }

    /* ─── Internal helpers ─────────────────────────────────── */

    /**
     * Persist all lines + their inventory rows/products for a purchase,
     * diffing against whatever the purchase already has rather than
     * wiping and rebuilding. This is the one piece of the "purchase
     * creates its own products" change that isn't purely additive: a
     * blind delete-and-recreate (the old behaviour for every edit) would
     * destroy any product a row already created the moment that product
     * picks up a photo, a description, or a website listing between the
     * purchase being entered and someone fixing a typo on the invoice.
     *
     * Matching is by the `id` the client echoes back for rows/lines it
     * already has — PurchaseRepository::find() round-trips them and the
     * create/edit Vue app sends them back unchanged. Anything without an
     * id is new. Anything the client no longer sends is removed.
     *
     *   - Existing LINE (id matches): template fields updated in place.
     *     NOT re-stamped onto already-created products — those become
     *     independent of the line the moment they exist. Only new rows
     *     added under this line are stamped from the (possibly
     *     just-edited) template.
     *   - Existing ROW (id matches): the purchase_product's mutable
     *     fields (qty, price, rack, dates, remarks, barcode) are updated
     *     in place. product_id and lot_code are left untouched. Carat
     *     weight is also pushed onto the linked product — correcting a
     *     weighed figure after the fact is the one field genuinely meant
     *     to stay in sync post-creation.
     *   - New ROW (no id): creates a Product, one auto-generated primary
     *     EAN-13 Barcode (so the "every product needs one" rule holds
     *     without staff having to do anything), and the purchase_product
     *     linking them.
     *   - Removed ROW (existing id, absent from payload): soft-deletes
     *     the purchase_product and, if safe (see productSafeToRetire()),
     *     the linked product too. Throws instead of silently orphaning a
     *     product someone has already started working on.
     *   - Removed LINE (existing id, absent from payload): same, for
     *     every row under it, then the line itself.
     */
    private function syncLines(Purchase $purchase, array $lines): void
    {
        // Loaded once — feeds PurchaseProduct::generateLotCode() below.
        $supplier = $purchase->supplier;

        $existingLines = $purchase->lines()->with('rows')->get()->keyBy('id');
        $seenLineIds   = [];

        foreach ($lines as $lineData) {
            $lineId       = $lineData['id'] ?? null;
            $existingLine = $lineId ? $existingLines->get((int) $lineId) : null;

            /** @var Category $category */
            $category = Category::findOrFail($lineData['category_id']);

            $type        = $lineData['type'] ?? PurchaseLine::TYPE_PIECE;
            $packageQty  = max(1, (int) ($lineData['package_qty'] ?? 1));
            $packageName = $lineData['package_name']
                ?? ($type === PurchaseLine::TYPE_PIECE ? 'Piece' : 'Box');

            // Box lines fan out into one inventory row (one product) per
            // box — Pack Qty drives the row count directly. Piece lines
            // are always exactly one row; the physical count lives on
            // that row's own qty field instead, so several identical
            // pieces can share one product. Enforced here regardless of
            // what the client sent — Pack Qty is disabled client-side for
            // Piece, but the server stays the source of truth.
            if ($type === PurchaseLine::TYPE_PIECE) {
                $packageQty = 1;
            }
            $totalQty = ($type === PurchaseLine::TYPE_PIECE) ? 1 : $packageQty;

            $caratDefault = isset($lineData['carat_weight']) && $lineData['carat_weight'] !== ''
                ? (float) $lineData['carat_weight']
                : null;

            $lineFields = [
                'category_id'       => $category->id,
                'title'             => $lineData['title'],
                'short_description' => $lineData['short_description'] ?? null,
                'full_description'  => $lineData['full_description']  ?? null,
                'country_of_origin_id' => $lineData['country_of_origin_id'] ?? null,
                'notes_tags'        => $lineData['notes_tags']        ?? null,
                'website_price'     => isset($lineData['website_price']) && $lineData['website_price'] !== ''
                    ? (float) $lineData['website_price']
                    : null,
                // Line-level toggle, applied to every product this line
                // creates/owns below (see the "New ROW" and "Existing
                // ROW" branches) — not just seeded once at creation.
                'website_enabled'   => (bool) ($lineData['website_enabled'] ?? false),
                'carat_weight'      => $caratDefault,
                'stone_type'        => $lineData['stone_type']   ?? null,
                'colour_grade'      => $lineData['colour_grade'] ?? null,
                'clarity_grade'     => $lineData['clarity_grade'] ?? null,
                'cut_shape'         => $lineData['cut_shape']    ?? null,
                'treatment'         => $lineData['treatment']    ?? null,
                'type'              => $type,
                'package_name'      => $packageName,
                'package_qty'       => $packageQty,
                'total_qty'         => $totalQty,
                'unit_contains'     => null,
                'remarks'           => $lineData['remarks'] ?? null,
            ];

            if ($existingLine) {
                $existingLine->fill($lineFields);
                $existingLine->save();
                $line = $existingLine;
            } else {
                $line = new PurchaseLine($lineFields);
                $purchase->lines()->save($line);
            }
            $seenLineIds[] = $line->id;

            $rowsPayload  = $lineData['rows'] ?? [];
            $existingRows = $existingLine ? $existingLine->rows->keyBy('id') : collect();
            $seenRowIds   = [];

            for ($i = 0; $i < $totalQty; $i++) {
                $r     = $rowsPayload[$i] ?? [];
                $rowId = $r['id'] ?? null;
                $existingRow = $rowId ? $existingRows->get((int) $rowId) : null;

                $qty         = max(0, (int) ($r['qty'] ?? 1));
                $caratWeight = isset($r['carat_weight']) && $r['carat_weight'] !== ''
                    ? (float) $r['carat_weight']
                    : $caratDefault;
                // Per-row selling price, falling back to the line's
                // default (set in the Add Item form) when the row hasn't
                // been given its own value — same fallback shape as
                // carat weight above. A box of 10 rarely sells for one
                // uniform price, so this is editable per row in the table.
                $websitePrice = isset($r['website_price']) && $r['website_price'] !== ''
                    ? (float) $r['website_price']
                    : $lineFields['website_price'];
                $price = (float) ($r['price'] ?? 0);

                if ($existingRow) {
                    $existingRow->fill([
                        'qty'              => $qty,
                        'carat_weight'     => $caratWeight,
                        'barcode'          => $r['barcode'] ?? null,
                        'rack_id'          => $r['rack_id'] ?? null,
                        'serial_number'    => $r['serial_number'] ?? null,
                        'price'            => $price,
                        'website_price'    => $websitePrice,
                        'expiry_date'      => $r['expiry_date'] ?? null,
                        'manufacture_date' => $r['manufacture_date'] ?? null,
                        'remarks'          => $r['remarks'] ?? null,
                    ]);
                    $existingRow->save();
                    $seenRowIds[] = $existingRow->id;

                    // Carat weight, selling price, and the website toggle
                    // are the fields genuinely meant to stay synced onto
                    // the product after creation — re-weighing a stone,
                    // repricing one item in a batch, or flipping whether
                    // this batch is listed are all normal corrections
                    // during a purchase edit.
                    if ($existingRow->product_id) {
                        $lineProduct = Product::find($existingRow->product_id);
                        if ($lineProduct) {
                            if ($caratWeight !== null) {
                                $lineProduct->carat_weight = $caratWeight;
                            }
                            if ($websitePrice !== null) {
                                $lineProduct->website_price = $websitePrice;
                            }
                            // Pushed through the model (not a bulk update)
                            // so booted()'s updating hook still stamps
                            // website_enabled_at/disabled_at and clears
                            // featured_product on disable — same as
                            // flipping it from the Products screen. Status
                            // is promoted to Active alongside enabling it,
                            // mirroring the create-time rule above.
                            $lineProduct->website_enabled = $line->website_enabled;
                            if ($line->website_enabled) {
                                $lineProduct->status = Product::STATUS_ACTIVE;
                            }
                            if ($lineProduct->isDirty()) {
                                $lineProduct->save();
                            }
                        }
                    }
                } else {
                    $product = Product::create([
                        'title'             => $line->title,
                        'sku'               => Product::generateSku($category),
                        'category_id'       => $category->id,
                        'short_description' => $line->short_description,
                        'full_description'  => $line->full_description,
                        'country_of_origin_id' => $line->country_of_origin_id,
                        'notes_tags'        => $line->notes_tags,
                        // Storefront listing needs BOTH website_enabled
                        // and an Active status (see WebsiteController) —
                        // the line's toggle drives both together so an
                        // enabled product is actually live, not stuck
                        // Draft.
                        'status'            => $line->website_enabled ? Product::STATUS_ACTIVE : Product::STATUS_DRAFT,
                        'pack_type'         => Product::PACK_TYPE_PIECE,
                        'website_price'     => $websitePrice,
                        'carat_weight'      => $caratWeight,
                        'stone_type'        => $line->stone_type,
                        'colour_grade'      => $line->colour_grade,
                        'clarity_grade'     => $line->clarity_grade,
                        'cut_shape'         => $line->cut_shape,
                        'treatment'         => $line->treatment,
                        'website_enabled'   => $line->website_enabled,
                        'featured_product'  => false,
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
                        'product_id'       => $product->id,
                        'qty'              => $qty,
                        'carat_weight'     => $caratWeight,
                        'barcode'          => $r['barcode'] ?? null,
                        'lot_code'         => PurchaseProduct::generateLotCode($supplier, $category),
                        'rack_id'          => $r['rack_id'] ?? null,
                        'serial_number'    => $r['serial_number'] ?? null,
                        'price'            => $price,
                        'website_price'    => $websitePrice,
                        'tax_percent'      => 0,
                        'tax_amount'       => 0,
                        'discount_percent' => 0,
                        'discount_amount'  => 0,
                        'expiry_date'      => $r['expiry_date'] ?? null,
                        'manufacture_date' => $r['manufacture_date'] ?? null,
                        'remarks'          => $r['remarks'] ?? null,
                    ]);
                    $line->rows()->save($row);
                    $seenRowIds[] = $row->id;
                }
            }

            if ($existingLine) {
                foreach ($existingRows->keys()->diff($seenRowIds) as $rid) {
                    $this->retireRow($existingRows->get($rid));
                }
            }
        }

        foreach ($existingLines->keys()->diff($seenLineIds) as $lid) {
            $line = $existingLines->get($lid);
            foreach ($line->rows as $row) {
                $this->retireRow($row);
            }
            $line->delete();
        }
    }

    /**
     * Soft-delete a purchase_product row and, if it's safe to, the
     * product it created. "Safe" means nobody has done anything to the
     * product beyond what row-creation auto-generated for it — no
     * photos, no website listing, no extra barcodes beyond the one
     * auto-created one. If the product has been touched, refuse rather
     * than silently orphan someone's work; they need to unlink or delete
     * it from the Products screen first.
     */
    private function retireRow(PurchaseProduct $row): void
    {
        $product = $row->product_id ? Product::find($row->product_id) : null;

        if ($product && ! $this->productSafeToRetire($product)) {
            throw new InvalidArgumentException(
                "Cannot remove this item: its product \"{$product->title}\" (SKU {$product->sku}) already has "
                . 'photos, an extra barcode, or a website listing. Unlink or delete it from the Products screen '
                . 'first, then try again.'
            );
        }

        $row->delete();

        if ($product) {
            $product->delete();
        }
    }

    private function productSafeToRetire(Product $product): bool
    {
        if ($product->website_enabled) {
            return false;
        }
        if ($product->getFirstMedia(Product::MEDIA_COLLECTION_PRIMARY)) {
            return false;
        }
        if ($product->getMedia(Product::MEDIA_COLLECTION_GALLERY)->isNotEmpty()) {
            return false;
        }
        if ($product->getFirstMedia(Product::MEDIA_COLLECTION_CERTIFICATE)) {
            return false;
        }
        if ($product->barcodes()->count() > 1) {
            return false;
        }

        return true;
    }

    /**
     * Recompute line and invoice totals from the persisted rows.
     * Net = carat_weight × price; tax and discount are not used.
     */
    private function recalculate(Purchase $purchase): void
    {
        $invoiceTotal = 0.0;

        foreach ($purchase->lines()->with('rows')->get() as $line) {
            $lineTotal = 0.0;

            foreach ($line->rows as $row) {
                $net        = (float) $row->carat_weight * (float) $row->price;
                $lineTotal += $net;
            }

            $line->subtotal = round($lineTotal, 2);
            $line->total    = round($lineTotal, 2);
            $line->save();

            $invoiceTotal += $lineTotal;
        }

        $purchase->subtotal       = round($invoiceTotal, 2);
        $purchase->discount_total = 0;
        $purchase->tax_total      = 0;
        $purchase->grand_total    = round($invoiceTotal, 2);
        $purchase->save();

        $this->recalculatePayments($purchase);
    }

    /**
     * Persist an initial batch of payments for a purchase, mirroring
     * SaleService::syncPayments(). Used by create() and by draft edits
     * that explicitly send a `payments` key.
     */
    private function syncPayments(Purchase $purchase, array $payments, bool $replace = false): void
    {
        if ($replace) {
            $purchase->payments()->each(fn (PurchasePayment $p) => $p->forceDelete());
        }

        foreach ($payments as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            if (abs($amount) < 0.001) {
                continue;
            }

            $purchase->payments()->save(new PurchasePayment([
                'payment_date'     => $row['payment_date']     ?? $purchase->purchase_date->toDateString(),
                'amount'           => $amount,
                'payment_method'   => $row['payment_method']   ?? PurchasePayment::METHOD_CASH,
                'reference_number' => $row['reference_number'] ?? null,
                'notes'            => $row['notes']            ?? null,
            ]));
        }
    }

    /**
     * Recompute paid_amount / due_amount / payment_status from the
     * purchase_payments rows. Mirrors SaleService::recalculatePayments().
     */
    private function recalculatePayments(Purchase $purchase): void
    {
        $paid  = (float) $purchase->payments()->sum('amount');
        $grand = (float) $purchase->grand_total;

        $due = round(max(0, $grand - $paid), 2);

        if ($paid <= 0.0001) {
            $status = Purchase::PAY_UNPAID;
        } elseif ($paid + 0.0001 >= $grand) {
            $status = Purchase::PAY_PAID;
        } else {
            $status = Purchase::PAY_PARTIAL;
        }

        $purchase->paid_amount    = round($paid, 2);
        $purchase->due_amount     = $due;
        $purchase->payment_status = $status;
        $purchase->save();
    }
}
