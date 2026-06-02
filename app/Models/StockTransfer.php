<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stock transfer document.
 *
 * Numbering: TRF-YYYYMM-#### (global per-month sequence, 4-digit pad).
 * Generated inside StockTransferService transactions to stay collision-safe.
 *
 * Lifecycle (state machine):
 *   draft       → in_transit   (post: OUT movements at from_location)
 *   in_transit  → received     (receive: IN movements at to_location)
 *   draft       → cancelled    (no stock impact)
 *   in_transit  → cancelled    (compensating IN at from_location)
 *   received    → (terminal — cannot cancel)
 */
class StockTransfer extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED   = 'received';
    public const STATUS_CANCELLED  = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_IN_TRANSIT,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    public const NUMBER_PREFIX = 'TRF';
    public const NUMBER_PAD    = 4;

    protected $fillable = [
        'transfer_number',
        'transfer_date',
        'from_location_id',
        'to_location_id',
        'status',
        'posted_at',
        'received_at',
        'cancelled_at',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'posted_at'     => 'datetime',
        'received_at'   => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    /* ─── Boot ─────────────────────────────────────────────── */

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (auth()->check()) {
                $t->created_by = $t->created_by ?? auth()->id();
                $t->updated_by = $t->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $t) {
            if (auth()->check()) {
                $t->updated_by = auth()->id();
            }
        });
    }

    /**
     * Next transfer number for the given date.
     * MUST be called inside the same DB transaction as the create() to
     * be safe against concurrent writes.
     */
    public static function generateTransferNumber(Carbon $date): string
    {
        $stub = self::NUMBER_PREFIX . '-' . $date->format('Ym') . '-';

        $max = self::withTrashed()
            ->where('transfer_number', 'like', $stub . '%')
            ->get(['transfer_number'])
            ->map(function ($row) use ($stub) {
                $tail = substr($row->transfer_number, strlen($stub));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;
        return $stub . str_pad((string) $next, self::NUMBER_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeDraft($q)      { return $q->where('status', self::STATUS_DRAFT); }
    public function scopeInTransit($q)  { return $q->where('status', self::STATUS_IN_TRANSIT); }
    public function scopeReceived($q)   { return $q->where('status', self::STATUS_RECEIVED); }
    public function scopeCancelled($q)  { return $q->where('status', self::STATUS_CANCELLED); }

    /* ─── Relationships ────────────────────────────────────── */

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class);
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
    public function isInTransit(): bool { return $this->status === self::STATUS_IN_TRANSIT; }
    public function isReceived():  bool { return $this->status === self::STATUS_RECEIVED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_TRANSIT => 'In Transit',
            default                 => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT      => 'badge-soft-warning',
            self::STATUS_IN_TRANSIT => 'badge-soft-info',
            self::STATUS_RECEIVED   => 'badge-soft-success',
            self::STATUS_CANCELLED  => 'badge-soft-danger',
            default                 => 'badge-soft-secondary',
        };
    }
}
