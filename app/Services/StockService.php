<?php

namespace App\Services;

use App\Models\CaratMovement;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * StockService — central authority over the stock_movements ledger.
 *
 * Every IN, OUT, transfer, and adjustment goes through this class so:
 *   1. The ledger semantics (append-only, signed math) stay consistent.
 *   2. Posting/cancelling a Purchase or Sale produces exactly the right
 *      movements without duplicating logic across the codebase.
 *   3. Balance queries have one canonical implementation.
 *
 * All write methods are idempotent against repeated calls when paired
 * with the `hasMovementsFromSource()` guard — callers should check
 * before recording so a re-post doesn't double-book stock.
 */
class StockService
{
    /* ─────────────────────────────────────────────────────────
     |  Read API — balance queries
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Current on-hand quantity for a specific piece at a specific
     * location. Per-piece granularity — this is THE canonical question
     * the system asks before allowing a sale.
     */
    public function onHandForPiece(int $purchaseProductId, int $locationId): int
    {
        $in  = (int) StockMovement::query()
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_IN)
            ->sum('qty');

        $out = (int) StockMovement::query()
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_OUT)
            ->sum('qty');

        return $in - $out;
    }

    /**
     * On-hand across every location for a piece. Useful for "where is
     * this piece" reports.
     *
     * Returns: [location_id => balance, ...] including only locations
     * with non-zero balances.
     */
    public function onHandForPieceByLocation(int $purchaseProductId): array
    {
        $rows = StockMovement::query()
            ->selectRaw('location_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->where('purchase_product_id', $purchaseProductId)
            ->groupBy('location_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal !== 0) {
                $out[(int) $r->location_id] = $bal;
            }
        }
        return $out;
    }

    /**
     * On-hand for a product at a location, summed across all pieces.
     * Used by the stock-report card and the sale-search fallback.
     */
    public function onHandForProduct(int $productId, int $locationId): int
    {
        $in  = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_IN)
            ->sum('qty');

        $out = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_OUT)
            ->sum('qty');

        return $in - $out;
    }

    /**
     * Global on-hand for a piece, summed across every location. Stock is
     * a single pool — purchases don't carry a location, so this is the
     * canonical balance the sale terminal asks about.
     */
    public function onHandForPieceGlobal(int $purchaseProductId): int
    {
        $rows = StockMovement::query()
            ->selectRaw('direction, SUM(qty) as total')
            ->where('purchase_product_id', $purchaseProductId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return (int) ($rows[StockMovement::DIRECTION_IN] ?? 0)
             - (int) ($rows[StockMovement::DIRECTION_OUT] ?? 0);
    }

    /* ─────────────────────────────────────────────────────────
     |  Read API — CT (carat) ledger
     | ─────────────────────────────────────────────────────────
     |  Independent of the qty ledger above: a purchase_products row's
     |  qty and carat_weight are two separate quantities (a qty=3 row can
     |  be 20ct + 7ct + 3ct, not 10ct each), so CT gets its own balance
     |  rather than being derived as qty × carat_weight.
     */

    /**
     * Remaining CT for a specific piece at a specific location.
     */
    public function remainingCaratForPiece(int $purchaseProductId, int $locationId): float
    {
        $rows = CaratMovement::query()
            ->selectRaw('direction, SUM(carat) as total')
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return round(
            (float) ($rows[CaratMovement::DIRECTION_IN] ?? 0)
            - (float) ($rows[CaratMovement::DIRECTION_OUT] ?? 0),
            3
        );
    }

    /**
     * Remaining CT for a piece, broken down by location. Mirrors
     * onHandForPieceByLocation() — [location_id => balance], positive
     * balances only.
     */
    public function remainingCaratForPieceByLocation(int $purchaseProductId): array
    {
        $rows = CaratMovement::query()
            ->selectRaw('location_id, '
                . 'SUM(CASE WHEN direction = ? THEN carat ELSE 0 END) as in_carat, '
                . 'SUM(CASE WHEN direction = ? THEN carat ELSE 0 END) as out_carat',
                [CaratMovement::DIRECTION_IN, CaratMovement::DIRECTION_OUT])
            ->where('purchase_product_id', $purchaseProductId)
            ->groupBy('location_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $bal = round((float) $r->in_carat - (float) $r->out_carat, 3);
            if ($bal > 0.0005) {
                $out[(int) $r->location_id] = $bal;
            }
        }
        return $out;
    }

    /**
     * Remaining CT for a piece, summed across every location — the
     * global figure the sale terminal and reports ask about (mirrors
     * onHandForPieceGlobal()).
     */
    public function remainingCaratForPieceGlobal(int $purchaseProductId): float
    {
        $rows = CaratMovement::query()
            ->selectRaw('direction, SUM(carat) as total')
            ->where('purchase_product_id', $purchaseProductId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return round(
            (float) ($rows[CaratMovement::DIRECTION_IN] ?? 0)
            - (float) ($rows[CaratMovement::DIRECTION_OUT] ?? 0),
            3
        );
    }

    /**
     * Remaining CT for a product at a location, summed across all of its
     * pieces. Mirrors onHandForProduct().
     */
    public function remainingCaratForProduct(int $productId, int $locationId): float
    {
        $rows = CaratMovement::query()
            ->selectRaw('direction, SUM(carat) as total')
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return round(
            (float) ($rows[CaratMovement::DIRECTION_IN] ?? 0)
            - (float) ($rows[CaratMovement::DIRECTION_OUT] ?? 0),
            3
        );
    }

    /**
     * Remaining CT for a product, summed across every location + piece.
     * Mirrors onHandForProductGlobal().
     */
    public function remainingCaratForProductGlobal(int $productId): float
    {
        $rows = CaratMovement::query()
            ->selectRaw('direction, SUM(carat) as total')
            ->where('product_id', $productId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return round(
            (float) ($rows[CaratMovement::DIRECTION_IN] ?? 0)
            - (float) ($rows[CaratMovement::DIRECTION_OUT] ?? 0),
            3
        );
    }

    /**
     * Pairs a set of StockMovement rows with their matching per-movement
     * CT amount, for ledger/history views that show one row per event
     * (Stock's product page, Barcode History). Returns [movement_id =>
     * carat|null] — null where there's no CT counterpart (product isn't
     * carat-tracked, or recordCarat() skipped a zero-carat leg).
     *
     * Matched by the same key StockService always writes a StockMovement
     * + CaratMovement pair under for one event: piece, location,
     * direction, reason, and source. A handful of reasons (adjustments,
     * opening) carry no source_id at all, so several unrelated events can
     * share that key — paired off in creation-order (id ascending) instead,
     * which holds because both ledgers are written back-to-back for the
     * same event, so their Nth-with-this-key rows line up.
     *
     * IMPORTANT: never call qty and carat display as if one derives the
     * other (e.g. carat_weight × qty) — this is exactly the bug this
     * method exists to avoid. Always resolve each row's real carat here.
     */
    public function caratForMovements(Collection $movements): array
    {
        if ($movements->isEmpty()) {
            return [];
        }

        $caratByKey = CaratMovement::query()
            ->whereIn('product_id', $movements->pluck('product_id')->unique())
            ->orderBy('id')
            ->get()
            ->groupBy(fn (CaratMovement $cm) => implode(':', [
                $cm->purchase_product_id, $cm->location_id, $cm->direction,
                $cm->reason, $cm->source_type, $cm->source_id, $cm->source_line_id,
            ]))
            ->map(fn ($group) => $group->values()->all())
            ->all();

        $result = [];
        foreach ($movements as $m) {
            $key = implode(':', [
                $m->purchase_product_id, $m->location_id, $m->direction,
                $m->reason, $m->source_type, $m->source_id, $m->source_line_id,
            ]);
            $cm = ! empty($caratByKey[$key]) ? array_shift($caratByKey[$key]) : null;
            $result[$m->id] = $cm?->carat !== null ? (float) $cm->carat : null;
        }

        return $result;
    }

    /**
     * Records one CT ledger row. Mirrors record() above. Silently
     * no-ops on a non-positive carat amount rather than throwing — most
     * callers pass through a possibly-null/possibly-zero line carat
     * (e.g. a sale line with no piece attached has nothing to book),
     * and forcing every caller to guard first would just duplicate this
     * check everywhere.
     */
    public function recordCarat(array $data): ?CaratMovement
    {
        $carat = round((float) ($data['carat'] ?? 0), 3);
        if ($carat <= 0) {
            return null;
        }
        if (! isset($data['purchase_product_id'], $data['product_id'], $data['location_id'])) {
            throw new InvalidArgumentException('recordCarat(): purchase_product_id, product_id, and location_id are all required.');
        }
        if (! isset($data['direction']) || ! in_array($data['direction'], CaratMovement::DIRECTIONS, true)) {
            throw new InvalidArgumentException("recordCarat(): direction must be 'in' or 'out'.");
        }
        if (empty($data['reason']) || ! array_key_exists($data['reason'], CaratMovement::REASONS)) {
            throw new InvalidArgumentException("recordCarat(): reason '{$data['reason']}' is not a recognised reason.");
        }

        return CaratMovement::create([
            'purchase_product_id' => $data['purchase_product_id'],
            'product_id'          => $data['product_id'],
            'location_id'         => $data['location_id'],
            'direction'           => $data['direction'],
            'carat'               => $carat,
            'reason'              => $data['reason'],
            'source_type'         => $data['source_type']    ?? null,
            'source_id'           => $data['source_id']      ?? null,
            'source_line_id'      => $data['source_line_id'] ?? null,
            'movement_date'       => $data['movement_date']  ?? now()->toDateString(),
            'notes'               => $data['notes']          ?? null,
        ]);
    }

    /**
     * Shared CT reversal: finds original CaratMovement rows for
     * (sourceType, sourceId, originalReason) restricted to $lineIds, and
     * inserts one compensating opposite-direction row per original with
     * $newReason. Used by both the sale-edit/refund/cancel paths and the
     * transfer-cancel path so the "find matching originals, reverse
     * each" logic isn't duplicated three times.
     *
     * Must be called ONCE against the full set of line ids for a given
     * reversal — never per already-fetched StockMovement row — because
     * one line's qty can span more than one location's movement, and
     * looping this alongside that would find and reverse the same CT
     * row multiple times.
     */
    private function reverseCaratForSource(
        string $sourceType,
        int $sourceId,
        string $originalReason,
        array $lineIds,
        string $newReason,
        ?string $notes = null
    ): void {
        $lineIds = array_values(array_filter($lineIds));
        if (empty($lineIds)) {
            return;
        }

        $originals = CaratMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('reason', $originalReason)
            ->whereIn('source_line_id', $lineIds)
            ->get();

        foreach ($originals as $orig) {
            $reverseDirection = $orig->isIn() ? CaratMovement::DIRECTION_OUT : CaratMovement::DIRECTION_IN;

            $this->recordCarat([
                'purchase_product_id' => $orig->purchase_product_id,
                'product_id'          => $orig->product_id,
                'location_id'         => $orig->location_id,
                'direction'           => $reverseDirection,
                'carat'               => (float) $orig->carat,
                'reason'              => $newReason,
                'source_type'         => $sourceType,
                'source_id'           => $sourceId,
                'source_line_id'      => $orig->source_line_id,
                'notes'               => $notes,
            ]);
        }
    }

    /**
     * Global on-hand for a product, summed across every location + piece.
     */
    public function onHandForProductGlobal(int $productId): int
    {
        $rows = StockMovement::query()
            ->selectRaw('direction, SUM(qty) as total')
            ->where('product_id', $productId)
            ->groupBy('direction')
            ->pluck('total', 'direction');

        return (int) ($rows[StockMovement::DIRECTION_IN] ?? 0)
             - (int) ($rows[StockMovement::DIRECTION_OUT] ?? 0);
    }

    /**
     * FIFO-ordered [purchase_product_id => global_balance], positives
     * only — used to allocate a product sale line across the global pool
     * when no specific piece is given.
     */
    public function availablePiecesForProductGlobal(int $productId): array
    {
        $rows = StockMovement::query()
            ->selectRaw('purchase_product_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->where('product_id', $productId)
            ->groupBy('purchase_product_id')
            ->get();

        $available = [];
        foreach ($rows as $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal > 0) {
                $available[(int) $r->purchase_product_id] = $bal;
            }
        }
        if (empty($available)) {
            return [];
        }

        $order = PurchaseProduct::whereIn('id', array_keys($available))
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $ordered = [];
        foreach ($order as $id) {
            if (isset($available[$id])) {
                $ordered[$id] = $available[$id];
            }
        }
        return $ordered;
    }

    /**
     * Pieces of a given product that have positive balance at the given
     * location. Used when a sale line references a product but no
     * specific piece — FIFO pick.
     *
     * Returns an array of [purchase_product_id => balance], ordered by
     * the piece's purchase date (oldest first).
     */
    public function availablePiecesForProduct(int $productId, int $locationId): array
    {
        // First, find all pieces for this product (via purchase_line)
        // that have any IN movement at this location. Then compute the
        // balance for each and filter positives.
        $pieceIds = StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->pluck('purchase_product_id')
            ->unique()
            ->values();

        if ($pieceIds->isEmpty()) {
            return [];
        }

        // Compute balance per piece in one query.
        $rows = StockMovement::query()
            ->selectRaw('purchase_product_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->whereIn('purchase_product_id', $pieceIds)
            ->where('location_id', $locationId)
            ->groupBy('purchase_product_id')
            ->get()
            ->keyBy('purchase_product_id');

        $available = [];
        foreach ($rows as $ppId => $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal > 0) {
                $available[(int) $ppId] = $bal;
            }
        }

        if (empty($available)) {
            return [];
        }

        // Sort by oldest piece first (FIFO). PurchaseProduct.created_at
        // is good enough since the inventory row is created when the
        // purchase is built.
        $order = PurchaseProduct::whereIn('id', array_keys($available))
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $ordered = [];
        foreach ($order as $id) {
            if (isset($available[$id])) {
                $ordered[$id] = $available[$id];
            }
        }

        return $ordered;
    }

    /**
     * Pieces sharing a given receiving-dock barcode that have positive
     * balance at the given location. Multiple purchase_products rows can
     * carry the same barcode -- it's the dock-scan value captured at
     * purchase entry, not the unique per-product retail barcode in the
     * `barcodes` table -- which is exactly what lets a box line's many
     * 1:1 rows be found and partially transferred as a group.
     *
     * Returns [purchase_product_id => balance], oldest piece first (FIFO).
     */
    public function availablePiecesForBarcode(string $barcode, int $locationId): array
    {
        $pieceIds = PurchaseProduct::where('barcode', $barcode)->pluck('id');
        if ($pieceIds->isEmpty()) {
            return [];
        }

        $rows = StockMovement::query()
            ->selectRaw('purchase_product_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->whereIn('purchase_product_id', $pieceIds)
            ->where('location_id', $locationId)
            ->groupBy('purchase_product_id')
            ->get();

        $available = [];
        foreach ($rows as $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal > 0) {
                $available[(int) $r->purchase_product_id] = $bal;
            }
        }
        if (empty($available)) {
            return [];
        }

        $order = PurchaseProduct::whereIn('id', array_keys($available))
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $ordered = [];
        foreach ($order as $id) {
            if (isset($available[$id])) {
                $ordered[$id] = $available[$id];
            }
        }

        return $ordered;
    }

    /**
     * Every piece with positive on-hand balance at a given location —
     * the "what's physically here" answer a stock audit starts from
     * (see StockAuditService::start()). Joins straight through to
     * purchase_products so the audit snapshot can be written in one
     * pass without a second round-trip per piece.
     *
     * Returns a Collection of rows: purchase_product_id, product_id,
     * barcode, lot_code, on_hand — ordered by purchase_product_id for
     * stable, resumable chunking on large (10k+ item) locations.
     */
    public function onHandPiecesForLocation(int $locationId, ?int $categoryId = null): Collection
    {
        $signedSql = "SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty "
            . "ELSE -stock_movements.qty END)";

        $query = DB::table('stock_movements')
            ->join('purchase_products', 'purchase_products.id', '=', 'stock_movements.purchase_product_id')
            ->where('stock_movements.location_id', $locationId)
            ->whereNull('stock_movements.deleted_at')
            ->whereNull('purchase_products.deleted_at');

        // Optional category ("Stone") scope so a stock audit can be
        // narrowed to a single category's stock at the location instead
        // of everything on hand there. Joined only when requested - the
        // unscoped call stays a straight two-table query, unchanged from
        // before this parameter existed. Filters on the same
        // stock_movements.product_id that flows into stock_audit_items,
        // so it matches exactly what the audit's own "Category" report
        // column later reads back off that row.
        if ($categoryId !== null) {
            $query->join('products', 'products.id', '=', 'stock_movements.product_id')
                ->whereNull('products.deleted_at')
                ->where('products.category_id', $categoryId);
        }

        return $query
            ->groupBy(
                'stock_movements.purchase_product_id',
                'stock_movements.product_id',
                'purchase_products.barcode',
                'purchase_products.lot_code'
            )
            ->select([
                'stock_movements.purchase_product_id',
                'stock_movements.product_id',
                'purchase_products.barcode',
                'purchase_products.lot_code',
                DB::raw($signedSql . ' as on_hand'),
            ])
            ->havingRaw($signedSql . ' > 0')
            ->orderBy('stock_movements.purchase_product_id')
            ->get();
    }

    /**
     * Have we already recorded movements for this source document?
     * Idempotency guard — call before re-posting to avoid double-booking.
     */
    public function hasMovementsFromSource(string $sourceType, int $sourceId, ?string $reason = null): bool
    {
        $q = StockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId);

        if ($reason !== null) {
            $q->where('reason', $reason);
        }

        return $q->exists();
    }

    /* ─────────────────────────────────────────────────────────
     |  Write API — record a single movement
     | ─────────────────────────────────────────────────────────
     */

    /**
     * The one and only INSERT entry point. All higher-level methods
     * funnel through here so the schema invariants (positive qty,
     * direction-reason coherence, etc.) live in one place.
     */
    public function record(array $data): StockMovement
    {
        // Schema invariants enforced here, not in DB constraints, so
        // we get clean PHP errors with context.
        if (! isset($data['purchase_product_id'], $data['product_id'], $data['location_id'])) {
            throw new InvalidArgumentException('record(): purchase_product_id, product_id, and location_id are all required.');
        }
        if (! isset($data['direction']) || ! in_array($data['direction'], StockMovement::DIRECTIONS, true)) {
            throw new InvalidArgumentException("record(): direction must be 'in' or 'out'.");
        }
        $qty = (int) ($data['qty'] ?? 0);
        if ($qty <= 0) {
            throw new InvalidArgumentException('record(): qty must be a positive integer.');
        }
        if (empty($data['reason']) || ! array_key_exists($data['reason'], StockMovement::REASONS)) {
            throw new InvalidArgumentException("record(): reason '{$data['reason']}' is not a recognised reason.");
        }

        return StockMovement::create([
            'purchase_product_id' => $data['purchase_product_id'],
            'product_id'          => $data['product_id'],
            'location_id'         => $data['location_id'],
            'direction'           => $data['direction'],
            'qty'                 => $qty,
            'reason'              => $data['reason'],
            'source_type'         => $data['source_type']    ?? null,
            'source_id'           => $data['source_id']      ?? null,
            'source_line_id'      => $data['source_line_id'] ?? null,
            'rack_id'             => $data['rack_id']        ?? null,
            'movement_date'       => $data['movement_date']  ?? now()->toDateString(),
            'notes'               => $data['notes']          ?? null,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Purchase
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Record all IN movements for a purchase being posted. One movement
     * per purchase_product row.
     *
     * Idempotent — if movements already exist for this purchase with
     * reason=purchase, the call is a no-op.
     */
    public function recordPurchasePosting(Purchase $purchase): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_PURCHASE, $purchase->id, StockMovement::REASON_PURCHASE)) {
            return;
        }

        // Use the purchase's own location; fall back to system default so
        // legacy / backfilled purchases still post cleanly.
        $locationId = $purchase->location_id
            ? (int) $purchase->location_id
            : $this->defaultLocationId();

        if (! $locationId) {
            throw new RuntimeException('Cannot post purchase: no location set and no default location available.');
        }

        DB::transaction(function () use ($purchase, $locationId) {
            $purchase->load('lines.rows');

            foreach ($purchase->lines as $line) {
                foreach ($line->rows as $row) {
                    if ((int) $row->qty <= 0) {
                        continue;
                    }
                    $this->record([
                        'purchase_product_id' => $row->id,
                        // Every row is its own product now (one row = one
                        // product) — read it off the row, not the line.
                        'product_id'          => $row->product_id,
                        'location_id'         => $locationId,
                        'direction'           => StockMovement::DIRECTION_IN,
                        'qty'                 => (int) $row->qty,
                        'reason'              => StockMovement::REASON_PURCHASE,
                        'source_type'         => StockMovement::SOURCE_PURCHASE,
                        'source_id'           => $purchase->id,
                        'source_line_id'      => $line->id,
                        'rack_id'             => $row->rack_id,
                        'movement_date'       => optional($purchase->purchase_date)->toDateString() ?? now()->toDateString(),
                    ]);

                    // Seed this row's own CT balance — whatever carat was
                    // recorded on the row, in full, regardless of qty.
                    // recordCarat() itself no-ops when carat_weight is
                    // null/0 (nothing to track).
                    $this->recordCarat([
                        'purchase_product_id' => $row->id,
                        'product_id'          => $row->product_id,
                        'location_id'         => $locationId,
                        'direction'           => CaratMovement::DIRECTION_IN,
                        'carat'               => (float) $row->carat_weight,
                        'reason'              => CaratMovement::REASON_PURCHASE,
                        'source_type'         => CaratMovement::SOURCE_PURCHASE,
                        'source_id'           => $purchase->id,
                        'source_line_id'      => $line->id,
                        'movement_date'       => optional($purchase->purchase_date)->toDateString() ?? now()->toDateString(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse a previously-posted purchase by emitting OUT counter-
     * movements. Used when a posted purchase is cancelled.
     *
     * Scoped to the purchase's CURRENTLY ACTIVE pieces only
     * (Purchase::purchaseProductIds()) — if the purchase was edited after
     * posting, superseded pieces were already reversed at edit time
     * (PurchaseService::updatePostedLines()) and must be left alone here.
     */
    public function reversePurchasePosting(Purchase $purchase): void
    {
        $activePieceIds = $purchase->purchaseProductIds();
        if (empty($activePieceIds)) {
            return;
        }

        // Find the original IN rows for the active pieces so we know
        // exactly what to counter. Don't re-derive from purchase_products
        // — if the rows were edited between post and cancel, we'd corrupt
        // the ledger.
        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_PURCHASE)
            ->where('source_id', $purchase->id)
            ->where('reason', StockMovement::REASON_PURCHASE)
            ->whereIn('purchase_product_id', $activePieceIds)
            ->get();

        if ($originals->isEmpty()) {
            return; // Nothing to reverse.
        }

        // Idempotency — have all of these active pieces already been
        // reversed (e.g. a retried cancel)?
        $reversedCount = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_PURCHASE)
            ->where('source_id', $purchase->id)
            ->where('reason', StockMovement::REASON_PURCHASE_CANCEL)
            ->whereIn('purchase_product_id', $originals->pluck('purchase_product_id')->unique())
            ->distinct()
            ->count('purchase_product_id');

        if ($reversedCount >= $originals->pluck('purchase_product_id')->unique()->count()) {
            return;
        }

        DB::transaction(function () use ($originals, $purchase) {
            foreach ($originals as $orig) {
                // Safety: never reverse stock that's already been sold/
                // transferred out. Refuse if any piece's balance would
                // go negative at its location.
                $balance = $this->onHandForPiece((int) $orig->purchase_product_id, (int) $orig->location_id);
                if ($balance < (int) $orig->qty) {
                    throw new RuntimeException(
                        "Cannot cancel purchase {$purchase->invoice_number}: piece #{$orig->purchase_product_id} "
                        . "at location #{$orig->location_id} has on-hand {$balance}, which is less than the "
                        . "original IN of {$orig->qty}. Reverse downstream sales/transfers first."
                    );
                }
            }

            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => $orig->qty,
                    'reason'              => StockMovement::REASON_PURCHASE_CANCEL,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $orig->source_line_id,
                    'rack_id'             => $orig->rack_id,
                    'notes'               => 'Reversal of original IN movement #' . $orig->id,
                ]);

                // Reverse this piece's ENTIRE remaining CT at this
                // location — the whole row is being un-purchased, not a
                // partial amount, so there's no allocation question here.
                $remainingCarat = $this->remainingCaratForPiece((int) $orig->purchase_product_id, (int) $orig->location_id);
                $this->recordCarat([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => CaratMovement::DIRECTION_OUT,
                    'carat'               => $remainingCarat,
                    'reason'              => CaratMovement::REASON_PURCHASE_CANCEL,
                    'source_type'         => CaratMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Reversal of original IN movement #' . $orig->id,
                ]);
            }
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Sale
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Hard availability check used before posting a sale. Returns a
     * list of error messages — empty array means "OK to post".
     *
     * Each sale line is checked individually: the specified piece must
     * have ≥ qty on hand at the sale's location. If no specific piece
     * is set, the sum across all available pieces of that product at
     * that location must be ≥ qty.
     */
    public function checkSaleAvailability(Sale $sale): array
    {
        $errors = [];

        // Stock is a single global pool — the sale's location_id is a
        // sales attribute, not a stock dimension, so it isn't used here.
        // Roll the lines up so multiple lines hitting the same piece are
        // aggregated for the check. Otherwise two lines of qty=2 each on
        // a piece with on-hand=3 would both pass individually.
        $byPiece      = [];
        $byProduct    = [];
        $caratByPiece   = [];
        $caratByProduct = [];

        $sale->load('lines');

        foreach ($sale->lines as $line) {
            $qty = (int) $line->qty;
            if ($qty <= 0) {
                continue;
            }

            if ($line->purchase_product_id) {
                $key = (int) $line->purchase_product_id;
                $byPiece[$key] = ($byPiece[$key] ?? 0) + $qty;

                // CT is independent of qty — only check it when the line
                // actually carries a seller-entered carat figure.
                if ($line->carat_weight !== null && (float) $line->carat_weight > 0) {
                    $caratByPiece[$key] = ($caratByPiece[$key] ?? 0) + (float) $line->carat_weight;
                }
            } else {
                $key = (int) $line->product_id;
                $byProduct[$key] = ($byProduct[$key] ?? 0) + $qty;

                // Same CT check, rolled up at product level — this line
                // will be FIFO-allocated across whichever of the
                // product's pieces have stock, so the pool to check
                // against is the product's total remaining CT, not any
                // one piece's.
                if ($line->carat_weight !== null && (float) $line->carat_weight > 0) {
                    $caratByProduct[$key] = ($caratByProduct[$key] ?? 0) + (float) $line->carat_weight;
                }
            }
        }

        foreach ($byPiece as $ppId => $needed) {
            $onHand = $this->onHandForPieceGlobal($ppId);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for piece #{$ppId}: need {$needed}, on hand {$onHand}.";
            }
        }

        foreach ($byProduct as $productId => $needed) {
            $onHand = $this->onHandForProductGlobal($productId);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for product #{$productId}: need {$needed}, on hand {$onHand}.";
            }
        }

        foreach ($caratByPiece as $ppId => $neededCarat) {
            $remainingCarat = $this->remainingCaratForPieceGlobal($ppId);
            if ($remainingCarat + 0.0005 < $neededCarat) {
                $errors[] = "Insufficient CT for piece #{$ppId}: need {$neededCarat}, remaining {$remainingCarat}.";
            }
        }

        foreach ($caratByProduct as $productId => $neededCarat) {
            $remainingCarat = $this->remainingCaratForProductGlobal($productId);
            if ($remainingCarat + 0.0005 < $neededCarat) {
                $errors[] = "Insufficient CT for product #{$productId}: need {$neededCarat}, remaining {$remainingCarat}.";
            }
        }

        return $errors;
    }

    /**
     * Record OUT movements for a sale being posted. For lines with an
     * explicit purchase_product_id we consume that exact piece; for
     * lines without one we FIFO-allocate across available pieces.
     *
     * Throws RuntimeException if availability fails. The caller should
     * call checkSaleAvailability() first for a clean error UX, but the
     * service still guards here so direct callers can't bypass it.
     */
    public function recordSalePosting(Sale $sale): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_SALE, $sale->id, StockMovement::REASON_SALE)) {
            return;
        }

        $errors = $this->checkSaleAvailability($sale);
        if (! empty($errors)) {
            throw new RuntimeException('Cannot post sale: ' . implode(' ', $errors));
        }

        DB::transaction(fn () => $this->bookSaleLinesOut($sale));
    }

    /**
     * Re-book a sale's OUT movements after an in-window edit. Unlike
     * recordSalePosting() this skips the source-level idempotency guard:
     * the edit flow has already reversed the previous OUT movements
     * (see reverseSaleForEdit), so the rebuilt lines must be booked fresh
     * even though earlier REASON_SALE rows still exist for this sale.
     *
     * Availability is re-checked against the just-restored global pool.
     */
    public function recordSalePostingForEdit(Sale $sale): void
    {
        $errors = $this->checkSaleAvailability($sale);
        if (! empty($errors)) {
            throw new RuntimeException('Cannot save sale: ' . implode(' ', $errors));
        }

        DB::transaction(fn () => $this->bookSaleLinesOut($sale));
    }

    /**
     * Book OUT movements for every current (active) line of a sale.
     * Shared core of recordSalePosting() and recordSalePostingForEdit().
     */
    private function bookSaleLinesOut(Sale $sale): void
    {
        $sale->load('lines');

        foreach ($sale->lines as $line) {
            $qty = (int) $line->qty;
            if ($qty <= 0) {
                continue;
            }

            if ($line->purchase_product_id) {
                // Exact piece specified — consume it from wherever it's held.
                $lineCarat = ($line->carat_weight !== null && (float) $line->carat_weight > 0)
                    ? (float) $line->carat_weight
                    : null;
                $this->bookPieceOut(
                    (int) $line->purchase_product_id,
                    (int) $line->product_id,
                    $qty,
                    $sale,
                    (int) $line->id,
                    $lineCarat
                );
                continue;
            }

            // No specific piece — FIFO-allocate across available pieces
            // of this product from the global pool. Snapshot the chosen
            // first piece back onto the SaleLine so refunds reverse it.
            $available = $this->availablePiecesForProductGlobal((int) $line->product_id);
            $remaining = $qty;
            $firstPpId = null;

            // CT is independent of qty — only meaningful when the line
            // actually carries an entered carat figure (e.g. a website
            // order, which never pins a specific piece up front). When
            // FIFO spans multiple pieces, split it proportionally by each
            // piece's qty share, with the last piece taken absorbing
            // whatever's left — same rule bookPieceOut() already applies
            // when a single piece's qty spans multiple locations.
            $lineCarat = ($line->carat_weight !== null && (float) $line->carat_weight > 0)
                ? (float) $line->carat_weight
                : null;

            // Resolve which pieces get drawn from (and how much of each)
            // up front, so the carat split below can tell which pick is
            // last without a second pass over $available.
            $picks  = [];
            $toFill = $remaining;
            foreach ($available as $ppId => $bal) {
                if ($toFill <= 0) break;
                $take = min((int) $bal, $toFill);
                if ($take <= 0) continue;
                $picks[$ppId] = $take;
                $toFill -= $take;
            }

            $caratRemaining = $lineCarat;
            $pickIndex      = 0;
            $pickCount      = count($picks);

            foreach ($picks as $ppId => $take) {
                $pickIndex++;
                $pieceCarat = null;
                if ($lineCarat !== null) {
                    $proportional = ($pickIndex === $pickCount)
                        ? $caratRemaining
                        : round($lineCarat * $take / $qty, 3);

                    // Different pieces can carry very different weights,
                    // so a pure qty-proportional split can ask more of
                    // one piece than it actually holds even when the
                    // combined total across every picked piece is
                    // enough. Cap each share at that piece's own
                    // remaining CT — never overdraw it — and let any
                    // shortfall roll forward onto whatever's picked next
                    // via $caratRemaining.
                    $pieceCarat = min($proportional, $this->remainingCaratForPieceGlobal($ppId));
                    $caratRemaining = round($caratRemaining - $pieceCarat, 3);
                }

                $this->bookPieceOut($ppId, (int) $line->product_id, $take, $sale, (int) $line->id, $pieceCarat);

                if ($firstPpId === null) {
                    $firstPpId = $ppId;
                }
                $remaining -= $take;
            }

            if ($lineCarat !== null && $caratRemaining > 0.0005) {
                // Every picked piece is now exhausted of its own CT and
                // there's still a balance left to place — the aggregate
                // check in checkSaleAvailability() only verifies the
                // product-level total, not that it's achievable given
                // each individual piece's own weight, so this can still
                // happen. Bail loudly rather than silently under-book
                // the ledger by the shortfall.
                throw new RuntimeException(
                    "CT allocation shortfall on sale line #{$line->id}: {$caratRemaining} ct "
                    . 'could not be placed across the pieces available for this product.'
                );
            }

            if ($remaining > 0) {
                // Should be unreachable given the availability check
                // above; bail loudly if math drifts.
                throw new RuntimeException(
                    "FIFO allocation underflow on sale line #{$line->id}: {$remaining} units unfilled."
                );
            }

            // Tag the line with the first piece consumed so the show
            // page has something useful to display and the reversal
            // can find a starting point. Cost snapshot from the piece.
            if ($firstPpId !== null) {
                $firstPiece = PurchaseProduct::find($firstPpId);
                SaleLine::where('id', $line->id)->update([
                    'purchase_product_id' => $firstPpId,
                    'cost_price'          => $firstPiece ? $firstPiece->price : $line->cost_price,
                ]);
            }
        }
    }

    /**
     * Book `qty` OUT for a single piece, drawing from whichever
     * location(s) actually hold positive balance. Stock is one global
     * pool; the sale's own location_id is a sales attribute and is NOT
     * used as a stock dimension here.
     *
     * When $caratWeight is given, books that exact CT figure OUT too —
     * split across the same location(s) in the same proportion as the
     * qty split (the common case is one location, so this is a no-op
     * split of 1). The last location in the split absorbs whatever CT
     * remains rather than its own proportional share, so rounding never
     * drifts the ledger away from the seller-entered total.
     */
    private function bookPieceOut(int $ppId, int $productId, int $qty, Sale $sale, int $lineId, ?float $caratWeight = null): void
    {
        $byLoc = $this->onHandForPieceByLocation($ppId); // [location_id => balance], positives only

        if (empty($byLoc)) {
            // Shouldn't happen after the availability check, but stay safe
            // so we never silently skip a movement.
            $def = $this->defaultLocationId();
            if (! $def) {
                throw new RuntimeException("No stock location available to book OUT for piece #{$ppId}.");
            }
            $byLoc = [$def => $qty];
        }

        $remaining      = $qty;
        $caratRemaining = $caratWeight;
        foreach ($byLoc as $locId => $bal) {
            if ($remaining <= 0) break;
            $take = min((int) $bal, $remaining);
            if ($take <= 0) continue;

            $this->record([
                'purchase_product_id' => $ppId,
                'product_id'          => $productId,
                'location_id'         => (int) $locId,
                'direction'           => StockMovement::DIRECTION_OUT,
                'qty'                 => $take,
                'reason'              => StockMovement::REASON_SALE,
                'source_type'         => StockMovement::SOURCE_SALE,
                'source_id'           => $sale->id,
                'source_line_id'      => $lineId,
                'movement_date'       => optional($sale->sale_date)->toDateString() ?? now()->toDateString(),
            ]);

            if ($caratWeight !== null) {
                $caratTake = ($take >= $remaining)
                    ? $caratRemaining
                    : round($caratWeight * $take / $qty, 3);
                $caratRemaining -= $caratTake;

                $this->recordCarat([
                    'purchase_product_id' => $ppId,
                    'product_id'          => $productId,
                    'location_id'         => (int) $locId,
                    'direction'           => CaratMovement::DIRECTION_OUT,
                    'carat'               => $caratTake,
                    'reason'              => CaratMovement::REASON_SALE,
                    'source_type'         => CaratMovement::SOURCE_SALE,
                    'source_id'           => $sale->id,
                    'source_line_id'      => $lineId,
                    'movement_date'       => optional($sale->sale_date)->toDateString() ?? now()->toDateString(),
                ]);
            }

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new RuntimeException("Stock booking underflow for piece #{$ppId}: {$remaining} unfilled.");
        }
    }

    /**
     * Reverse the OUT movements for a specific set of (about-to-be-removed)
     * sale lines during an in-window edit, returning their stock to the
     * global pool via compensating IN rows (REASON_SALE_EDIT_REVERSE).
     *
     * Called by SaleService::update() BEFORE the old lines are soft-deleted
     * and rebuilt, so recordSalePostingForEdit() then books the new lines
     * against a correctly-restored pool. Because the reversal is scoped to
     * the old line IDs and the new OUT rows reference the new line IDs, a
     * later refund/cancel (which reverses only the sale's *active* lines)
     * never double-counts the superseded movements.
     */
    public function reverseSaleForEdit(Sale $sale, array $lineIds): void
    {
        if (empty($lineIds)) {
            return;
        }

        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_SALE)
            ->where('source_id', $sale->id)
            ->where('reason', StockMovement::REASON_SALE)
            ->whereIn('source_line_id', $lineIds)
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($originals, $sale, $lineIds) {
            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $orig->qty,
                    'reason'              => StockMovement::REASON_SALE_EDIT_REVERSE,
                    'source_type'         => StockMovement::SOURCE_SALE,
                    'source_id'           => $sale->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Reversed for edit of sale ' . $sale->sale_number,
                ]);
            }
            // Called once against the full line-id set (not per
            // StockMovement row above) — a line's qty can span more than
            // one location's worth of movements, and looping the CT
            // reversal alongside them would find + reverse the same CT
            // row multiple times.
            $this->reverseCaratForSource(
                CaratMovement::SOURCE_SALE, $sale->id, CaratMovement::REASON_SALE,
                $lineIds, CaratMovement::REASON_SALE_EDIT_REVERSE,
                'Reversed for edit of sale ' . $sale->sale_number
            );
        });
    }

    /**
     * Reverse a posted sale's OUT movements by inserting matching INs.
     * Reason indicates whether the trigger was a refund or a cancellation.
     *
     * Scoped to the sale's CURRENTLY ACTIVE lines only — if the sale was
     * edited after posting, the superseded lines were already reversed at
     * edit time (reverseSaleForEdit) and must be left alone here.
     */
    public function reverseSalePosting(Sale $sale, string $reason): void
    {
        if (! in_array($reason, [StockMovement::REASON_SALE_RETURN, StockMovement::REASON_SALE_CANCEL], true)) {
            throw new InvalidArgumentException("reverseSalePosting(): unsupported reason '{$reason}'.");
        }

        // Idempotency — if a counter-row already exists with the same
        // reason, skip.
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_SALE, $sale->id, $reason)) {
            return;
        }

        $lineIds = $sale->lines()->pluck('id')->all();

        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_SALE)
            ->where('source_id', $sale->id)
            ->where('reason', StockMovement::REASON_SALE)
            ->whereIn('source_line_id', $lineIds)
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($originals, $reason, $sale, $lineIds) {
            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $orig->qty,
                    'reason'              => $reason,
                    'source_type'         => StockMovement::SOURCE_SALE,
                    'source_id'           => $sale->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Reversal of original OUT movement #' . $orig->id,
                ]);
            }
            $this->reverseCaratForSource(
                CaratMovement::SOURCE_SALE, $sale->id, CaratMovement::REASON_SALE,
                $lineIds, $reason,
                'Reversal of original OUT CT movement'
            );
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Stock Transfer
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Post a stock transfer: emit OUT movements at from_location.
     * Pieces are now "in transit" — gone from source, not yet at dest.
     */
    public function recordTransferPosting(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_OUT)) {
            return;
        }

        $transfer->load('lines');

        // Pre-flight availability check at source — same per-piece rules.
        $errors = [];
        $byPiece = [];
        $caratByPiece = [];
        foreach ($transfer->lines as $line) {
            if ((int) $line->qty <= 0) continue;
            $key = (int) $line->purchase_product_id;
            $byPiece[$key] = ($byPiece[$key] ?? 0) + (int) $line->qty;

            if ($line->carat_weight !== null && (float) $line->carat_weight > 0) {
                $caratByPiece[$key] = ($caratByPiece[$key] ?? 0) + (float) $line->carat_weight;
            }
        }
        foreach ($byPiece as $ppId => $needed) {
            $onHand = $this->onHandForPiece($ppId, (int) $transfer->from_location_id);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for piece #{$ppId} at source: need {$needed}, on hand {$onHand}.";
            }
        }
        foreach ($caratByPiece as $ppId => $neededCarat) {
            $remainingCarat = $this->remainingCaratForPiece($ppId, (int) $transfer->from_location_id);
            if ($remainingCarat + 0.0005 < $neededCarat) {
                $errors[] = "Insufficient CT for piece #{$ppId} at source: need {$neededCarat}, remaining {$remainingCarat}.";
            }
        }
        if (! empty($errors)) {
            throw new RuntimeException('Cannot post transfer: ' . implode(' ', $errors));
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->lines as $line) {
                if ((int) $line->qty <= 0) continue;

                $this->record([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->from_location_id,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => (int) $line->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_OUT,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'movement_date'       => optional($transfer->transfer_date)->toDateString() ?? now()->toDateString(),
                ]);

                $this->recordCarat([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->from_location_id,
                    'direction'           => CaratMovement::DIRECTION_OUT,
                    'carat'               => (float) $line->carat_weight,
                    'reason'              => CaratMovement::REASON_TRANSFER_OUT,
                    'source_type'         => CaratMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'movement_date'       => optional($transfer->transfer_date)->toDateString() ?? now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * Receive a transfer: emit IN movements at to_location.
     */
    public function recordTransferReceipt(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_IN)) {
            return;
        }

        $transfer->load('lines');

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->lines as $line) {
                if ((int) $line->qty <= 0) continue;

                $this->record([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->to_location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => (int) $line->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_IN,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $line->to_rack_id,
                    'movement_date'       => now()->toDateString(),
                ]);

                $this->recordCarat([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->to_location_id,
                    'direction'           => CaratMovement::DIRECTION_IN,
                    'carat'               => (float) $line->carat_weight,
                    'reason'              => CaratMovement::REASON_TRANSFER_IN,
                    'source_type'         => CaratMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'movement_date'       => now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * Cancel an in-transit transfer: return pieces to from_location via
     * compensating IN movements.
     */
    public function reverseTransferPosting(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_CANCEL_OUT)) {
            return;
        }

        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_STOCK_TRANSFER)
            ->where('source_id', $transfer->id)
            ->where('reason', StockMovement::REASON_TRANSFER_OUT)
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        $lineIds = $originals->pluck('source_line_id')->filter()->unique()->values()->all();

        DB::transaction(function () use ($originals, $transfer, $lineIds) {
            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $orig->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_CANCEL_OUT,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Cancellation of in-transit transfer; restoring to source.',
                ]);
            }
            $this->reverseCaratForSource(
                CaratMovement::SOURCE_STOCK_TRANSFER, $transfer->id, CaratMovement::REASON_TRANSFER_OUT,
                $lineIds, CaratMovement::REASON_TRANSFER_CANCEL_OUT,
                'Cancellation of in-transit transfer; restoring to source.'
            );
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Manual adjustments
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Manual stock adjustment. delta > 0 → IN; delta < 0 → OUT.
     * Used for stock-take corrections, breakage, etc.
     */
    public function adjust(
        int $purchaseProductId,
        int $productId,
        int $locationId,
        int $delta,
        string $notes = ''
    ): StockMovement {
        if ($delta === 0) {
            throw new InvalidArgumentException('adjust(): delta must be non-zero.');
        }

        $direction = $delta > 0 ? StockMovement::DIRECTION_IN : StockMovement::DIRECTION_OUT;
        $reason    = $delta > 0 ? StockMovement::REASON_ADJUSTMENT_IN : StockMovement::REASON_ADJUSTMENT_OUT;

        if ($delta < 0) {
            $onHand = $this->onHandForPiece($purchaseProductId, $locationId);
            if ($onHand < abs($delta)) {
                throw new RuntimeException(
                    "Cannot adjust down by " . abs($delta) . ": piece #{$purchaseProductId} only has on-hand {$onHand}."
                );
            }
        }

        return $this->record([
            'purchase_product_id' => $purchaseProductId,
            'product_id'          => $productId,
            'location_id'         => $locationId,
            'direction'           => $direction,
            'qty'                 => abs($delta),
            'reason'              => $reason,
            'source_type'         => StockMovement::SOURCE_STOCK_ADJUSTMENT,
            'source_id'           => null,
            'notes'               => $notes ?: null,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
     |  Helpers
     | ─────────────────────────────────────────────────────────
     */

    public function defaultLocationId(): ?int
    {
        $id = Location::where('is_default', true)->value('id');
        if ($id) return (int) $id;

        $id = Location::orderBy('id')->value('id');
        return $id ? (int) $id : null;
    }
}
