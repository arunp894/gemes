<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Packing document (header). Turns raw, unpacked purchase stock into
 * sellable Products -- see PackingService. PurchaseService no longer
 * creates Products at all; this is now the only place that does.
 *
 * Numbering: PACK-YYYYMM-####, global per-month sequence, mirrors
 * StockTransfer::generateTransferNumber().
 *
 * Lifecycle:
 *   draft   -> posted    (post: consumes source rows, credits the new
 *                         output Products/PurchaseProduct rows)
 *   draft   -> cancelled (no stock impact yet)
 *   posted  -> cancelled (compensating reversal)
 */
class Packing extends Model
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

    public const NUMBER_PREFIX = 'PACK';
    public const NUMBER_PAD    = 4;

    protected $fillable = [
        'packing_number',
        'packing_date',
        'location_id',
        'status',
        'posted_at',
        'cancelled_at',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'packing_date' => 'date',
        'posted_at'    => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /* ─── Boot ─────────────────────────────────────────────── */

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

    /**
     * Next packing number for the given date. Must be called inside the
     * same DB transaction as the create() to stay collision-safe --
     * mirrors StockTransfer::generateTransferNumber().
     */
    public static function generatePackingNumber(Carbon $date): string
    {
        $stub = self::NUMBER_PREFIX . '-' . $date->format('Ym') . '-';

        $max = self::withTrashed()
            ->where('packing_number', 'like', $stub . '%')
            ->get(['packing_number'])
            ->map(function ($row) use ($stub) {
                $tail = substr($row->packing_number, strlen($stub));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;
        return $stub . str_pad((string) $next, self::NUMBER_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeDraft($q)     { return $q->where('status', self::STATUS_DRAFT); }
    public function scopePosted($q)    { return $q->where('status', self::STATUS_POSTED); }
    public function scopeCancelled($q) { return $q->where('status', self::STATUS_CANCELLED); }

    /* ─── Relationships ────────────────────────────────────── */

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(PackingSource::class);
    }

    /**
     * The new PurchaseProduct rows (and, via ->product, the new sellable
     * Products) this packing created.
     */
    public function outputs(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class, 'packing_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* ─── State predicates ─────────────────────────────────── */

    public function isDraft():     bool { return $this->status === self::STATUS_DRAFT; }
    public function isPosted():    bool { return $this->status === self::STATUS_POSTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CANCELLED => 'Cancelled',
            default                 => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'badge-soft-warning',
            self::STATUS_POSTED    => 'badge-soft-success',
            self::STATUS_CANCELLED => 'badge-soft-danger',
            default                => 'badge-soft-secondary',
        };
    }
}
