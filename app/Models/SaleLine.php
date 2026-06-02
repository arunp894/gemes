<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sale line item — ONE row per product line on the sale.
 *
 * Differs from PurchaseLine in two ways:
 *   1. No inner-pack expansion. A sale moves qty of one product on one
 *      row; cartons are handled at purchase time and consumed piece-wise
 *      at sale time.
 *   2. Optionally linked to the specific purchase_products row that
 *      supplied the piece — enables exact margin and FIFO/LIFO costing.
 */
class SaleLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sale_id',
        'product_id',
        'purchase_product_id',
        'barcode',
        'qty',
        'unit_price',
        'tax_percent',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'total',
        'cost_price',
        'notes',
    ];

    protected $casts = [
        'qty'              => 'integer',
        'unit_price'       => 'decimal:2',
        'tax_percent'      => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'subtotal'         => 'decimal:2',
        'total'            => 'decimal:2',
        'cost_price'       => 'decimal:2',
    ];

    /* ─── Relationships ────────────────────────────────────── */

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The specific inventory row this line consumed (if known).
     */
    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchase_product_id');
    }

    /* ─── Money helpers ────────────────────────────────────── */

    public function gross(): float
    {
        return (float) $this->qty * (float) $this->unit_price;
    }

    /**
     * Margin = (selling price net) − (cost × qty). Returns 0 when cost
     * isn't known so it never poisons aggregate reports.
     */
    public function margin(): float
    {
        $cost = (float) $this->cost_price * (float) $this->qty;
        return (float) $this->total - $cost;
    }
}
