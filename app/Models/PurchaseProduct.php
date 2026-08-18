<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Actual inventory row. Each record = ONE stockable unit (one piece,
 * one box). Owns its barcode, rack, expiry, and money fields.
 */
class PurchaseProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'purchase_line_id',
        'product_id',
        'qty',
        'carat_weight',
        'barcode',
        'lot_code',
        'rack_id',
        'serial_number',
        'price',
        'website_price',
        'tax_percent',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'expiry_date',
        'manufacture_date',
        'remarks',
    ];

    protected $casts = [
        'qty'              => 'integer',
        'carat_weight'     => 'decimal:3',
        'price'            => 'decimal:2',
        'website_price'    => 'decimal:2',
        'tax_percent'      => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'expiry_date'      => 'date',
        'manufacture_date' => 'date',
    ];

    /* ─── Relationships ────────────────────────────────────── */

    public function line(): BelongsTo
    {
        return $this->belongsTo(PurchaseLine::class, 'purchase_line_id');
    }

    /**
     * The individual Product this row created (or, for historical rows
     * predating this column, null — identity for those still comes via
     * line->product_id). One row = one product going forward.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    /* ─── Convenience accessors for stock queries ─────────── */

    /**
     * Walks through line → purchase to reach the parent invoice without
     * eager-loading. Use ->load('line.purchase') first if hitting a list.
     */
    public function getPurchaseAttribute(): ?Purchase
    {
        return $this->line?->purchase;
    }

    /**
     * The product this row represents, preferring its own direct link
     * (every row created after the product_id column was added) and
     * falling back to the line's shared product (historical rows from
     * before purchases created their own products). Views should read
     * this — not ->product or ->line->product directly — so they work
     * for both eras without an if/else in every template.
     */
    public function getResolvedProductAttribute(): ?Product
    {
        return $this->product ?: $this->line?->product;
    }

    /* ─── Money helpers ────────────────────────────────────── */

    /**
     * net = carat_weight × price
     */
    public function gross(): float
    {
        return (float) $this->carat_weight * (float) $this->price;
    }

    public function net(): float
    {
        return $this->gross();
    }

    /**
     * Digit → letter cipher for printing a coded cost price on labels
     * (standard jewellery-trade practice — staff can read the cost back
     * off the tag, customers just see letters). Ten distinct letters,
     * one per digit 0–9.
     *
     * @var array<string, string>
     */
    public const PRICE_CODE_MAP = [
        '0' => 'S',
        '1' => 'W',
        '2' => 'O',
        '3' => 'N',
        '4' => 'D',
        '5' => 'E',
        '6' => 'R',
        '7' => 'F',
        '8' => 'U',
        '9' => 'L',
    ];

    /**
     * This row's price, rounded to the nearest whole rupee and run
     * through PRICE_CODE_MAP. e.g. price 1234.56 -> "1235" -> "WOND".
     */
    public function priceCode(): string
    {
        $rounded = (string) (int) round((float) $this->price);

        return strtr($rounded, self::PRICE_CODE_MAP);
    }

    /* ─── Lot code generator ───────────────────────────────── */

    /**
     * Generate this row's lot/tracking code. Format: SS-CCC-UUU
     *   SS  = first two letters of the supplier's display name
     *   CCC = this category's sequence number for that supplier —
     *         assigned once per (supplier, category) pair and reused on
     *         every later purchase in the same category from the same
     *         supplier
     *   UUU = running count of individual rows (pieces/boxes) ever
     *         recorded for this exact supplier+category pair
     *
     * NOTE: keyed on CATEGORY, not product. Under the old flow a
     * purchase line pointed at one pre-existing, stable product, so the
     * middle segment could mean "this product's sequence for this
     * supplier" and reuse it across separate purchases. Now every row
     * creates a brand-new product, so product_id is never stable across
     * purchases — category_id is the thing that's actually stable and
     * repeatable, so it's what preserves the original intent of this
     * segment ("the Nth distinct thing we've bought from this
     * supplier").
     *
     * NOTE: two suppliers sharing their first two letters (e.g. "Amber
     * Gems" and "Amber Traders") can produce identical codes — the format
     * only carries 2 letters of supplier identity, so this is a known
     * limitation of the requested scheme, not a bug.
     *
     * Must be called inside the same DB transaction as the row's save()
     * to be safe against concurrent writes — mirrors
     * Purchase::generateInvoiceNumber(). PurchaseService::syncLines()
     * already runs inside one.
     */
    public static function generateLotCode(Supplier $supplier, Category $category): string
    {
        $stub = self::lotCodeStub($supplier, $category);

        $max = self::withTrashed()
            ->where('lot_code', 'like', $stub . '%')
            ->pluck('lot_code')
            ->map(fn ($code) => (int) (explode('-', $code)[2] ?? 0))
            ->max();

        $unitSeq = str_pad((string) (((int) $max) + 1), 3, '0', STR_PAD_LEFT);

        return $stub . $unitSeq;
    }

    /**
     * Read-only preview of the next $count lot codes for a supplier +
     * category — used by the create/edit form to show what will be
     * assigned on save. Writes nothing, so a stale preview (e.g. someone
     * else saves a purchase in between) can never cause a collision; the
     * real codes are always regenerated inside the save transaction.
     */
    public static function previewLotCodes(Supplier $supplier, Category $category, int $count): array
    {
        $stub = self::lotCodeStub($supplier, $category);

        $max = self::withTrashed()
            ->where('lot_code', 'like', $stub . '%')
            ->pluck('lot_code')
            ->map(fn ($code) => (int) (explode('-', $code)[2] ?? 0))
            ->max();

        $next  = ((int) $max) + 1;
        $codes = [];

        for ($i = 0; $i < max(1, $count); $i++) {
            $codes[] = $stub . str_pad((string) ($next + $i), 3, '0', STR_PAD_LEFT);
        }

        return $codes;
    }

    /**
     * Resolve the "SS-CCC-" stub shared by generateLotCode() and
     * previewLotCodes(): supplier letters + this category's per-supplier
     * sequence number (assigned once, reused after).
     */
    private static function lotCodeStub(Supplier $supplier, Category $category): string
    {
        $name    = $supplier->company_name ?: $supplier->name ?: '';
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $prefix  = str_pad(substr($letters, 0, 2), 2, 'X') . '-';

        // Has this exact (supplier, category) pair already been assigned
        // a sequence number under this prefix? Reuse it if so — including
        // trashed rows/lines, since editing a purchase can soft-delete
        // and rebuild rows (see PurchaseService::syncLines()).
        $existing = self::withTrashed()
            ->where('lot_code', 'like', $prefix . '%')
            ->whereHas('line', function ($q) use ($category) {
                $q->withTrashed()->where('category_id', $category->id);
            })
            ->value('lot_code');

        if ($existing) {
            $categorySeq = explode('-', $existing)[1] ?? '001';
        } else {
            $maxSeq = self::withTrashed()
                ->where('lot_code', 'like', $prefix . '%')
                ->pluck('lot_code')
                ->map(fn ($code) => (int) (explode('-', $code)[1] ?? 0))
                ->max();

            $categorySeq = str_pad((string) (((int) $maxSeq) + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefix . $categorySeq . '-';
    }
}
