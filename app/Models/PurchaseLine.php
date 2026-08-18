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
    public const TYPE_BOX    = 'box';

    /** @deprecated Use TYPE_BOX */
    public const TYPE_UNIT   = 'box';
    /** @deprecated Use TYPE_BOX */
    public const TYPE_CARTON = 'box';

    public const TYPES = [
        self::TYPE_PIECE,
        self::TYPE_BOX,
    ];

    protected $fillable = [
        'purchase_id',
        'product_id',
        // Product-creation template. One line -> N products (one per
        // generated row), all stamped from these fields at creation
        // time. Not re-propagated to already-created products on a
        // later edit — see PurchaseService::syncLines().
        'title',
        'category_id',
        'short_description',
        'full_description',
        'country_of_origin',
        'country_of_origin_id',
        'notes_tags',
        // Optional seed for the created product(s)' listing price — staff
        // can leave this blank and set it later per product instead.
        'website_price',
        'carat_weight',
        'stone_type',
        'colour_grade',
        'clarity_grade',
        'cut_shape',
        'treatment',
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
        'carat_weight'  => 'decimal:3',
        'website_price' => 'decimal:2',
        'category_id'   => 'integer',
        'country_of_origin_id' => 'integer',
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

    /**
     * The category new-style rows are stamped from. Also what
     * PurchaseProduct::generateLotCode() keys its per-supplier sequence
     * number on (see that method for why).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    public function countryOfOrigin(): BelongsTo
    {
        return $this->belongsTo(CountryOfOrigin::class);
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
