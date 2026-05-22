<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product-level row on a purchase. ONE per product, regardless of how
 * many inner-pack inventory rows hang off it.
 */
class PurchaseLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_PIECE  = 'piece';
    public const TYPE_BOX   = 'box';
    public const TYPE_UNIT   = 'unit';
    public const TYPE_CARTON = 'carton';

    public const TYPES = [
        self::TYPE_PIECE,
        self::TYPE_BOX,
        self::TYPE_UNIT,
        self::TYPE_CARTON,
    ];

    protected $fillable = [
        'purchase_id',
        'product_id',
        'type',
        'package_name',
        'package_qty',
        'total_qty',
        'unit_contains',
        'subtotal',
        'total',
        'remarks',
    ];

    protected $casts = [
        'package_qty'   => 'integer',
        'total_qty'     => 'integer',
        'unit_contains' => 'integer',
        'subtotal'      => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    /* ─── Relationships ────────────────────────────────────── */

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function isPiece(): bool
    {
        return $this->type === self::TYPE_PIECE;
    }
    public function isBox(): bool
    {
        return $this->type === self::TYPE_BOX;
    }
    public function isUnit(): bool
    {
        return $this->type === self::TYPE_UNIT;
    }
    public function isCarton(): bool
    {
        return $this->type === self::TYPE_CARTON;
    }
}
