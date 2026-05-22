<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase invoice (header).
 *
 * Numbering: PREFIX-YYYYMM-####, where PREFIX comes from the supplier's
 * invoice_prefix and #### is sequential per-supplier per-month, padded to
 * 4 digits. The generator runs inside the PurchaseService transaction
 * so two concurrent saves can't collide.
 */
class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_POSTED    = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_POSTED,
        self::STATUS_CANCELLED,
    ];

    public const TAX_NONE      = 'none';
    public const TAX_CGST_SGST = 'cgst_sgst';
    public const TAX_IGST      = 'igst';

    public const TAX_TYPES = [
        self::TAX_NONE,
        self::TAX_CGST_SGST,
        self::TAX_IGST,
    ];

    public const NUMBER_PAD = 4;

    protected $fillable = [
        'invoice_number',
        'purchase_date',
        'supplier_id',
        'tax_type',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'paid_amount',
        'due_amount',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date'  => 'date',
        'subtotal'       => 'decimal:2',
        'tax_total'      => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'paid_amount'    => 'decimal:2',
        'due_amount'     => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (auth()->check()) {
                $p->created_by = $p->created_by ?? auth()->id();
                $p->updated_by = $p->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $p) {
            if (auth()->check()) {
                $p->updated_by = auth()->id();
            }
        });
    }

    /* ─── Invoice number generator ─────────────────────────── */

    /**
     * Build the next invoice number for a given supplier and date.
     * Format: {prefix}-{YYYYMM}-{####}.
     *
     * NOTE: Must be called inside the same DB transaction as the
     * Purchase::create() call to be safe against concurrent writes.
     * The PurchaseService::create() method wraps both in a transaction.
     */
    public static function generateInvoiceNumber(Supplier $supplier, Carbon $date): string
    {
        $prefix = strtoupper(
            $supplier->invoice_prefix
                ?: str_replace('-', '', $supplier->supplier_code)
        );

        $yyyymm = $date->format('Ym');
        $stub   = "{$prefix}-{$yyyymm}-";

        // Look at ALL purchases (incl. soft-deleted) so we never recycle a
        // number — invoice numbers are physical-document references.
        $max = self::withTrashed()
            ->where('supplier_id', $supplier->id)
            ->where('invoice_number', 'like', $stub . '%')
            ->get(['invoice_number'])
            ->map(function ($row) use ($stub) {
                $tail = substr($row->invoice_number, strlen($stub));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;

        return $stub . str_pad((string) $next, self::NUMBER_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeDraft($query)     { return $query->where('status', self::STATUS_DRAFT); }
    public function scopePosted($query)    { return $query->where('status', self::STATUS_POSTED); }
    public function scopeCancelled($query) { return $query->where('status', self::STATUS_CANCELLED); }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('purchase_date', [$from, $to]);
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    /**
     * Convenience: every actual inventory row across all lines.
     */
    public function inventoryRows(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchaseProduct::class,
            PurchaseLine::class,
            'purchase_id',
            'purchase_line_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    public function isDraft():     bool { return $this->status === self::STATUS_DRAFT; }
    public function isPosted():    bool { return $this->status === self::STATUS_POSTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_POSTED    => 'badge-soft-success',
            self::STATUS_DRAFT     => 'badge-soft-warning',
            self::STATUS_CANCELLED => 'badge-soft-danger',
            default                => 'badge-soft-secondary',
        };
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Computed CGST/SGST split for display on the invoice template.
     * Returns ['cgst' => x, 'sgst' => y, 'igst' => z] depending on tax_type.
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
