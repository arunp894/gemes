<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales invoice (header).
 *
 * Numbering: SALE-YYYYMM-#### (global per-month sequence, 4-digit padded).
 * The generator runs inside SaleService transactions to stay collision-safe.
 *
 * Status lifecycle: draft → posted → completed; refunded / cancelled are
 * branches off the main path. See migration for full semantics.
 *
 * Import columns (nullable, only set by SaleImportService):
 *   external_ref      – Platform order reference (eBay Sales Record #, etc.)
 *   external_order_id – Higher-level platform order ID
 *   import_batch_id   – UUID of the upload batch
 */
class Sale extends Model
{
    use HasFactory;
    use SoftDeletes;

    /* ─── Status constants ─────────────────────────────────── */
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_POSTED    = 'posted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_POSTED,
        self::STATUS_COMPLETED,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    /* ─── Tax type (mirrors Purchase) ──────────────────────── */
    public const TAX_NONE      = 'none';
    public const TAX_CGST_SGST = 'cgst_sgst';
    public const TAX_IGST      = 'igst';

    public const TAX_TYPES = [
        self::TAX_NONE,
        self::TAX_CGST_SGST,
        self::TAX_IGST,
    ];

    /* ─── Payment status ───────────────────────────────────── */
    public const PAY_UNPAID  = 'unpaid';
    public const PAY_PARTIAL = 'partial';
    public const PAY_PAID    = 'paid';

    public const PAYMENT_STATUSES = [
        self::PAY_UNPAID,
        self::PAY_PARTIAL,
        self::PAY_PAID,
    ];

    public const NUMBER_PREFIX = 'SALE';
    public const NUMBER_PAD    = 4;

    protected $fillable = [
        'sale_number',
        'sale_date',
        'customer_id',
        'location_id',
        'channel_id',
        'salesperson_id',
        'tax_type',
        'subtotal',
        'tax_total',
        'discount_total',
        'shipping_charge',
        'grand_total',
        'paid_amount',
        'balance_due',
        'payment_status',
        'status',
        'note',
        // import traceability columns
        'external_ref',
        'external_order_id',
        'import_batch_id',
        // audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date'       => 'date',
        'subtotal'        => 'decimal:2',
        'tax_total'       => 'decimal:2',
        'discount_total'  => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance_due'     => 'decimal:2',
    ];

    /* ─── Boot ─────────────────────────────────────────────── */

    protected static function booted(): void
    {
        static::creating(function (self $s) {
            if (auth()->check()) {
                $s->created_by = $s->created_by ?? auth()->id();
                $s->updated_by = $s->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $s) {
            if (auth()->check()) {
                $s->updated_by = auth()->id();
            }
        });
    }

    /* ─── Invoice number generator ─────────────────────────── */

    /**
     * Build the next sale number for a given date.
     * Format: SALE-YYYYMM-####.
     *
     * MUST be called inside the same DB transaction as Sale::create()
     * to be safe against concurrent writes. SaleService wraps both.
     */
    public static function generateSaleNumber(Carbon $date): string
    {
        $stub = self::NUMBER_PREFIX . '-' . $date->format('Ym') . '-';

        $max = self::withTrashed()
            ->where('sale_number', 'like', $stub . '%')
            ->get(['sale_number'])
            ->map(function ($row) use ($stub) {
                $tail = substr($row->sale_number, strlen($stub));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;

        return $stub . str_pad((string) $next, self::NUMBER_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeDraft($query)     { return $query->where('status', self::STATUS_DRAFT); }
    public function scopePosted($query)    { return $query->where('status', self::STATUS_POSTED); }
    public function scopeCompleted($query) { return $query->where('status', self::STATUS_COMPLETED); }
    public function scopeRefunded($query)  { return $query->where('status', self::STATUS_REFUNDED); }
    public function scopeCancelled($query) { return $query->where('status', self::STATUS_CANCELLED); }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('sale_date', [$from, $to]);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /** Filter sales that came from a bulk import. */
    public function scopeImported($query)
    {
        return $query->whereNotNull('import_batch_id');
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ─── Status helpers ───────────────────────────────────── */

    public function isDraft():     bool { return $this->status === self::STATUS_DRAFT; }
    public function isPosted():    bool { return $this->status === self::STATUS_POSTED; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isRefunded():  bool { return $this->status === self::STATUS_REFUNDED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function isImported(): bool
    {
        return $this->import_batch_id !== null;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'badge-soft-warning',
            self::STATUS_POSTED    => 'badge-soft-info',
            self::STATUS_COMPLETED => 'badge-soft-success',
            self::STATUS_REFUNDED  => 'badge-soft-secondary',
            self::STATUS_CANCELLED => 'badge-soft-danger',
            default                => 'badge-soft-secondary',
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAY_PAID    => 'Paid',
            self::PAY_PARTIAL => 'Partial',
            default           => 'Unpaid',
        };
    }

    public function paymentStatusBadgeClass(): string
    {
        return match ($this->payment_status) {
            self::PAY_PAID    => 'badge-soft-success',
            self::PAY_PARTIAL => 'badge-soft-warning',
            default           => 'badge-soft-danger',
        };
    }

    /**
     * CGST/SGST/IGST split for the printed invoice, mirroring Purchase.
     */
    public function getTaxBreakdownAttribute(): array
    {
        $total = (float) $this->tax_total;
        return match ($this->tax_type) {
            self::TAX_CGST_SGST => ['cgst' => $total / 2, 'sgst' => $total / 2, 'igst' => 0],
            self::TAX_IGST      => ['cgst' => 0, 'sgst' => 0, 'igst' => $total],
            default             => ['cgst' => 0, 'sgst' => 0, 'igst' => 0],
        };
    }
}
