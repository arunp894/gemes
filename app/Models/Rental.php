<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stone rental (header): stones go out to a customer from `start_at` to `due_at`,
 * stock leaves on creation (rent_out) and comes back on return (rent_return).
 * Overdue is derived (active + past due_at), never stored.
 */
class Rental extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_RETURNED  = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    public const NUMBER_PREFIX = 'RNT';

    protected $fillable = [
        'rent_number', 'customer_id', 'location_id', 'start_at', 'due_at', 'returned_at', 'status',
        'rent_total', 'deposit_total', 'rent_paid', 'deposit_paid', 'deposit_refunded', 'deduction_total',
        'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_at'         => 'datetime',
        'due_at'           => 'datetime',
        'returned_at'      => 'datetime',
        'rent_total'       => 'decimal:2',
        'deposit_total'    => 'decimal:2',
        'rent_paid'        => 'decimal:2',
        'deposit_paid'     => 'decimal:2',
        'deposit_refunded' => 'decimal:2',
        'deduction_total'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (auth()->check()) {
                $r->created_by = $r->created_by ?? auth()->id();
                $r->updated_by = $r->updated_by ?? auth()->id();
            }
        });
        static::updating(function (self $r) {
            if (auth()->check()) {
                $r->updated_by = auth()->id();
            }
        });
    }

    /** RNT-YYYYMM-####; call inside the creating transaction. */
    public static function generateNumber(Carbon $date): string
    {
        $stub = self::NUMBER_PREFIX . '-' . $date->format('Ym') . '-';

        $max = self::withTrashed()->where('rent_number', 'like', $stub . '%')->pluck('rent_number')
            ->map(fn ($n) => (int) substr($n, strlen($stub)))->max();

        return $stub . str_pad((string) ((int) $max + 1), 4, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ─── */

    /**
     * "Now" as a wall-clock time in the business timezone, expressed in the app timezone, so it compares
     * directly with start_at / due_at (which store the times exactly as staff typed them).
     */
    public static function wallNow(): Carbon
    {
        return Carbon::parse(now(config('app.business_timezone'))->format('Y-m-d H:i:s'));
    }

    public function scopeOverdue($q)
    {
        return $q->where('status', self::STATUS_ACTIVE)->where('due_at', '<', self::wallNow());
    }

    /* ─── Relations ─── */

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function lines(): HasMany      { return $this->hasMany(RentalLine::class); }
    public function payments(): HasMany   { return $this->hasMany(RentalPayment::class); }
    public function logs(): HasMany       { return $this->hasMany(RentalLog::class)->latest(); }

    /* ─── State helpers ─── */

    public function isActive(): bool    { return $this->status === self::STATUS_ACTIVE; }
    public function isReturned(): bool  { return $this->status === self::STATUS_RETURNED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function isOverdue(): bool
    {
        return $this->isActive() && $this->due_at->lt(self::wallNow());
    }

    /** Whole days past due_at, at least 1 once overdue (0 when not overdue). */
    public function overdueDays(): int
    {
        return $this->isOverdue() ? max(1, (int) floor($this->due_at->diffInHours(self::wallNow()) / 24)) : 0;
    }

    /** Deposit still held: collected minus refunded minus what was kept as deductions. */
    public function depositHeld(): float
    {
        return round((float) $this->deposit_paid - (float) $this->deposit_refunded - (float) $this->deduction_total, 2);
    }

    public function statusLabel(): string
    {
        return $this->isOverdue() ? 'Overdue' : ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        if ($this->isOverdue()) {
            return 'badge-soft-danger';
        }
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'badge-soft-info',
            self::STATUS_RETURNED => 'badge-soft-success',
            default               => 'badge-soft-secondary',
        };
    }
}
