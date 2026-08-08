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
        'qty',
        'carat_weight',
        'barcode',
        'lot_code',
        'rack_id',
        'serial_number',
        'price',
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
     * Generate this row's lot/tracking code. Format: SS-PPP-UUU
     *   SS  = first two letters of the supplier's display name
     *   PPP = this product's sequence number for that supplier — assigned
     *         once per (supplier, product) pair and reused on every later
     *         purchase of the same product from the same supplier
     *   UUU = running count of individual rows (pieces/boxes) ever
     *         recorded for this exact supplier+product pair — a Box line
     *         with several rows gets one number per row, same as several
     *         separate purchases would
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
    public static function generateLotCode(Supplier $supplier, Product $product): string
    {
        $stub = self::lotCodeStub($supplier, $product);

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
     * product — used by the create/edit form to show what will be
     * assigned on save. Writes nothing, so a stale preview (e.g. someone
     * else saves a purchase in between) can never cause a collision; the
     * real codes are always regenerated inside the save transaction.
     */
    public static function previewLotCodes(Supplier $supplier, Product $product, int $count): array
    {
        $stub = self::lotCodeStub($supplier, $product);

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
     * Resolve the "SS-PPP-" stub shared by generateLotCode() and
     * previewLotCodes(): supplier letters + this product's per-supplier
     * sequence number (assigned once, reused after).
     */
    private static function lotCodeStub(Supplier $supplier, Product $product): string
    {
        $name    = $supplier->company_name ?: $supplier->name ?: '';
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $prefix  = str_pad(substr($letters, 0, 2), 2, 'X') . '-';

        // Has this exact (supplier, product) pair already been assigned a
        // sequence number under this prefix? Reuse it if so — including
        // trashed rows/lines, since editing a purchase soft-deletes and
        // rebuilds its lines (see PurchaseService::updatePostedLines()).
        $existing = self::withTrashed()
            ->where('lot_code', 'like', $prefix . '%')
            ->whereHas('line', function ($q) use ($product) {
                $q->withTrashed()->where('product_id', $product->id);
            })
            ->value('lot_code');

        if ($existing) {
            $productSeq = explode('-', $existing)[1] ?? '001';
        } else {
            $maxSeq = self::withTrashed()
                ->where('lot_code', 'like', $prefix . '%')
                ->pluck('lot_code')
                ->map(fn ($code) => (int) (explode('-', $code)[1] ?? 0))
                ->max();

            $productSeq = str_pad((string) (((int) $maxSeq) + 1), 3, '0', STR_PAD_LEFT);
        }

        return $prefix . $productSeq . '-';
    }
}
