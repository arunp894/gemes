<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StockMovement — single row in the append-only stock ledger.
 *
 * **DO NOT MUTATE OR DELETE** these rows in normal flow. Corrections
 * happen via compensating rows (insert an OUT to cancel an IN, etc.).
 * SoftDeletes is present only to satisfy the project-wide convention.
 *
 * The math everywhere is:
 *   balance = SUM(qty WHERE direction='in') − SUM(qty WHERE direction='out')
 *
 * source_type values match StockMovement::SOURCE_* and serve as a thin
 * polymorphic backlink to the originating document.
 */
class StockMovement extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const DIRECTION_IN  = 'in';
    public const DIRECTION_OUT = 'out';

    public const DIRECTIONS = [
        self::DIRECTION_IN,
        self::DIRECTION_OUT,
    ];

    /* ─── Reasons ─────────────────────────────────────────── */

    public const REASON_PURCHASE            = 'purchase';
    public const REASON_PURCHASE_CANCEL     = 'purchase_cancel';
    public const REASON_SALE                = 'sale';
    public const REASON_SALE_RETURN         = 'sale_return';
    public const REASON_SALE_CANCEL         = 'sale_cancel';
    public const REASON_SALE_EDIT_REVERSE   = 'sale_edit_reverse';
    public const REASON_TRANSFER_OUT        = 'transfer_out';
    public const REASON_TRANSFER_IN         = 'transfer_in';
    public const REASON_TRANSFER_CANCEL_OUT = 'transfer_cancel_out';
    public const REASON_ADJUSTMENT_IN       = 'adjustment_in';
    public const REASON_ADJUSTMENT_OUT      = 'adjustment_out';
    public const REASON_OPENING             = 'opening';

    public const REASONS = [
        self::REASON_PURCHASE            => 'Purchase',
        self::REASON_PURCHASE_CANCEL     => 'Purchase Cancelled',
        self::REASON_SALE                => 'Sale',
        self::REASON_SALE_RETURN         => 'Sale Return',
        self::REASON_SALE_CANCEL         => 'Sale Cancelled',
        self::REASON_SALE_EDIT_REVERSE   => 'Sale Edited (stock returned)',
        self::REASON_TRANSFER_OUT        => 'Transfer Out',
        self::REASON_TRANSFER_IN         => 'Transfer In',
        self::REASON_TRANSFER_CANCEL_OUT => 'Transfer Cancelled',
        self::REASON_ADJUSTMENT_IN       => 'Adjustment (In)',
        self::REASON_ADJUSTMENT_OUT      => 'Adjustment (Out)',
        self::REASON_OPENING             => 'Opening Stock',
    ];

    /* ─── Source types ────────────────────────────────────── */

    public const SOURCE_PURCHASE          = 'purchase';
    public const SOURCE_SALE              = 'sale';
    public const SOURCE_STOCK_TRANSFER    = 'stock_transfer';
    public const SOURCE_STOCK_ADJUSTMENT  = 'stock_adjustment';

    protected $fillable = [
        'purchase_product_id',
        'product_id',
        'location_id',
        'direction',
        'qty',
        'reason',
        'source_type',
        'source_id',
        'source_line_id',
        'rack_id',
        'movement_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'qty'           => 'integer',
        'movement_date' => 'date',
    ];

    /* ─── Boot ─────────────────────────────────────────────── */

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (auth()->check() && empty($m->created_by)) {
                $m->created_by = auth()->id();
            }
            if (empty($m->movement_date)) {
                $m->movement_date = now()->toDateString();
            }
        });

        // Defensive: refuse updates after the row is persisted.
        // The ledger is append-only; any caller trying to mutate is a
        // logic error and should fail loudly.
        static::updating(function (self $m) {
            // We allow updated_at to advance during seed re-runs but
            // block changes to ledger-meaningful columns.
            $protected = [
                'purchase_product_id', 'product_id', 'location_id',
                'direction', 'qty', 'reason',
                'source_type', 'source_id', 'source_line_id',
            ];
            foreach ($protected as $col) {
                if ($m->isDirty($col)) {
                    throw new \LogicException(
                        "StockMovement is append-only. Column '{$col}' cannot be modified. "
                        . 'Insert a compensating row instead.'
                    );
                }
            }
        });
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeIn($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    public function scopeForPiece($query, int $purchaseProductId, ?int $locationId = null)
    {
        $q = $query->where('purchase_product_id', $purchaseProductId);
        if ($locationId !== null) {
            $q->where('location_id', $locationId);
        }
        return $q;
    }

    public function scopeForProduct($query, int $productId, ?int $locationId = null)
    {
        $q = $query->where('product_id', $productId);
        if ($locationId !== null) {
            $q->where('location_id', $locationId);
        }
        return $q;
    }

    public function scopeFromSource($query, string $type, int $id)
    {
        return $query->where('source_type', $type)->where('source_id', $id);
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function purchaseProduct(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class, 'purchase_product_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function isIn():  bool { return $this->direction === self::DIRECTION_IN; }
    public function isOut(): bool { return $this->direction === self::DIRECTION_OUT; }

    public function signedQty(): int
    {
        return $this->isIn() ? (int) $this->qty : -((int) $this->qty);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }

    public function reasonBadgeClass(): string
    {
        return match ($this->reason) {
            self::REASON_PURCHASE,
            self::REASON_TRANSFER_IN,
            self::REASON_ADJUSTMENT_IN,
            self::REASON_SALE_RETURN,
            self::REASON_SALE_CANCEL,
            self::REASON_SALE_EDIT_REVERSE,
            self::REASON_OPENING            => 'badge-soft-success',

            self::REASON_SALE,
            self::REASON_TRANSFER_OUT,
            self::REASON_ADJUSTMENT_OUT     => 'badge-soft-danger',

            self::REASON_PURCHASE_CANCEL,
            self::REASON_TRANSFER_CANCEL_OUT => 'badge-soft-warning',

            default                          => 'badge-soft-secondary',
        };
    }
}
