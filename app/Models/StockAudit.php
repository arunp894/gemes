<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stock audit (physical stock-take) header.
 *
 * Numbering: AUD-YYYYMM-#### (global per-month sequence, 4-digit pad),
 * same shape as StockTransfer::generateTransferNumber().
 *
 * Lifecycle:
 *   in_progress → completed   (finalize — no ledger writes by itself)
 *   in_progress → cancelled   (abandon — no ledger writes)
 *   completed / cancelled     → terminal
 *
 * At creation, a snapshot of every piece with positive on-hand balance
 * at the chosen location is copied into stock_audit_items — see
 * StockAuditService::start(). Scanning only ever matches against that
 * frozen snapshot, so stock movements happening elsewhere while the
 * floor is being counted can't shift the goalposts mid-walk.
 *
 * Completing/cancelling never touches the stock ledger by itself — see
 * StockAuditService::writeOffMissing() for the explicit, separate
 * action that does.
 */
class StockAudit extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_CANCELLED   = 'cancelled';

    public const STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const NUMBER_PREFIX = 'AUD';
    public const NUMBER_PAD    = 4;

    protected $fillable = [
        'audit_number',
        'audit_date',
        'location_id',
        'status',
        'expected_total',
        'matched_total',
        'started_at',
        'completed_at',
        'cancelled_at',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'audit_date'     => 'date',
        'expected_total' => 'integer',
        'matched_total'  => 'integer',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    /* ─── Boot ─────────────────────────────────────────────── */

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            if (auth()->check()) {
                $a->created_by = $a->created_by ?? auth()->id();
                $a->updated_by = $a->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $a) {
            if (auth()->check()) {
                $a->updated_by = auth()->id();
            }
        });
    }

    /**
     * Next audit number for the given date. Must be called inside the
     * same DB transaction as the row's save() to stay collision-safe —
     * mirrors StockTransfer::generateTransferNumber().
     */
    public static function generateAuditNumber(Carbon $date): string
    {
        $stub = self::NUMBER_PREFIX . '-' . $date->format('Ym') . '-';

        $max = self::withTrashed()
            ->where('audit_number', 'like', $stub . '%')
            ->get(['audit_number'])
            ->map(function ($row) use ($stub) {
                $tail = substr($row->audit_number, strlen($stub));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;

        return $stub . str_pad((string) $next, self::NUMBER_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeInProgress($q) { return $q->where('status', self::STATUS_IN_PROGRESS); }
    public function scopeCompleted($q)  { return $q->where('status', self::STATUS_COMPLETED); }
    public function scopeCancelled($q)  { return $q->where('status', self::STATUS_CANCELLED); }

    /* ─── Relationships ────────────────────────────────────── */

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAuditItem::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(StockAuditScan::class);
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

    public function isInProgress(): bool { return $this->status === self::STATUS_IN_PROGRESS; }
    public function isCompleted():  bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled():  bool { return $this->status === self::STATUS_CANCELLED; }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED   => 'Completed',
            self::STATUS_CANCELLED   => 'Cancelled',
            default                  => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'badge-soft-info',
            self::STATUS_COMPLETED   => 'badge-soft-success',
            self::STATUS_CANCELLED   => 'badge-soft-danger',
            default                  => 'badge-soft-secondary',
        };
    }

    /* ─── Progress helpers ─────────────────────────────────── */

    public function missingTotal(): int
    {
        return max(0, (int) $this->expected_total - (int) $this->matched_total);
    }

    public function progressPercent(): float
    {
        if ((int) $this->expected_total <= 0) {
            return 100.0;
        }

        return round((min((int) $this->matched_total, (int) $this->expected_total) / (int) $this->expected_total) * 100, 1);
    }
}
