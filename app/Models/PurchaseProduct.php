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
        'barcode',
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
     * gross = qty × price
     * net   = gross − discount_amount + tax_amount
     */
    public function gross(): float
    {
        return (float) $this->qty * (float) $this->price;
    }

    public function net(): float
    {
        return $this->gross() - (float) $this->discount_amount + (float) $this->tax_amount;
    }
}
