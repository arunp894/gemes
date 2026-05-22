<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Storage rack / bin.
 *
 * Auto-codes follow the same pattern as Supplier / Category: RACK-0001.
 */
class Rack extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    public const CODE_PREFIX = 'RACK';
    public const CODE_PAD    = 4;

    protected $fillable = [
        'code',
        'name',
        'location',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $rack) {
            if (empty($rack->code)) {
                $rack->code = self::generateNextCode();
            } else {
                $rack->code = strtoupper($rack->code);
            }

            if (auth()->check()) {
                $rack->created_by = $rack->created_by ?? auth()->id();
                $rack->updated_by = $rack->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $rack) {
            if (auth()->check()) {
                $rack->updated_by = auth()->id();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $prefix = self::CODE_PREFIX . '-';

        $max = self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->get(['code'])
            ->map(function ($row) use ($prefix) {
                $tail = substr($row->code, strlen($prefix));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;

        return $prefix . str_pad((string) $next, self::CODE_PAD, '0', STR_PAD_LEFT);
    }

    /* ─── Scopes ───────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('code');
    }

    /* ─── Relationships ────────────────────────────────────── */

    public function purchaseProducts(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
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

    public function isActive(): bool
    {
        return (bool) $this->status === true;
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Active' : 'Inactive';
    }

    public function statusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-soft-success' : 'badge-soft-danger';
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->code . ' — ' . $this->name;
    }
}
