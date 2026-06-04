<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Location — a physical place where sales can happen.
 *
 * Mirrors the Supplier pattern: auto-generated code (LOC-0001), audit
 * columns wired in booted(), SoftDeletes, and a tight set of helpers
 * for the UI. Stock is intentionally NOT modelled here — locations are
 * pure identity + address + ownership.
 */
class Location extends Model
{
    use HasFactory;
    use SoftDeletes;

    /* -----------------------------------------------------------------
     |  Constants
     | -----------------------------------------------------------------
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    public const CODE_PREFIX = 'LOC';
    public const CODE_PAD    = 4;

    /**
     * Allowed location types. Adding a new type? Append here and to the
     * Store/Update FormRequest rules — no migration needed (column is a
     * plain VARCHAR, not a DB-level enum).
     */
    public const TYPES = [
        'warehouse'  => 'Warehouse',
        'showroom'   => 'Showroom',
        'store'      => 'Retail Store',
        'booth'      => 'Booth / Counter',
        'exhibition' => 'Exhibition',
        'online'     => 'Online Channel',
        'other'      => 'Other',
    ];

    protected $fillable = [
        'location_code',
        'name',
        'type',
        'description',
        'manager_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'zip_code',
        'phone',
        'email',
        'latitude',
        'longitude',
        'is_default',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status'     => 'boolean',
        'latitude'   => 'decimal:7',
        'longitude'  => 'decimal:7',
    ];

    /* -----------------------------------------------------------------
     |  Model events
     | -----------------------------------------------------------------
     |  - Auto-generate location_code if blank (LOC-0001 sequence).
     |  - Bind created_by / updated_by from the authenticated user.
     |  - Enforce the single-default invariant: when a row is being saved
     |    with is_default=true, demote every other row first.
     */
    protected static function booted(): void
    {
        static::creating(function (self $location) {
            if (empty($location->location_code)) {
                $location->location_code = self::generateNextCode();
            } else {
                $location->location_code = strtoupper($location->location_code);
            }

            if (auth()->check()) {
                $location->created_by = $location->created_by ?? auth()->id();
                $location->updated_by = $location->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $location) {
            if (auth()->check()) {
                $location->updated_by = auth()->id();
            }
        });

        // Single-default invariant. Demote siblings inside a transaction so
        // a concurrent save can't leave us with two defaults.
        static::saving(function (self $location) {
            if ($location->is_default) {
                DB::transaction(function () use ($location) {
                    self::where('is_default', true)
                        ->when($location->exists, fn ($q) => $q->where('id', '!=', $location->id))
                        ->update(['is_default' => false]);
                });
            }
        });
    }

    /**
     * Build the next sequential location code, e.g. LOC-0001.
     * Includes soft-deleted rows so codes are never recycled.
     */
    public static function generateNextCode(): string
    {
        $prefix = self::CODE_PREFIX . '-';

        $max = self::withTrashed()
            ->where('location_code', 'like', $prefix . '%')
            ->get(['location_code'])
            ->map(function ($row) use ($prefix) {
                $tail = substr($row->location_code, strlen($prefix));
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max();

        $next = ((int) $max) + 1;

        return $prefix . str_pad((string) $next, self::CODE_PAD, '0', STR_PAD_LEFT);
    }

    /* -----------------------------------------------------------------
     |  Scopes
     | -----------------------------------------------------------------
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */
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

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function typeBadgeClass(): string
    {
        // Visual differentiation per type — picked to read well on light bg.
        return match ($this->type) {
            'warehouse'  => 'badge-soft-primary',
            'showroom'   => 'badge-soft-info',
            'store'      => 'badge-soft-success',
            'booth'      => 'badge-soft-warning',
            'exhibition' => 'badge-soft-purple',
            'online'     => 'badge-soft-dark',
            default      => 'badge-soft-secondary',
        };
    }

    /**
     * Compact one-line address for tables/cards. Drops empty parts.
     */
    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);
        return $parts ? implode(', ', $parts) : '';
    }

    /**
     * Full multi-line address for the detail view.
     */
    public function getFullAddressAttribute(): string
    {
        $line = trim(implode(' ', array_filter([$this->address_line1, $this->address_line2])));
        $cityLine = trim(implode(', ', array_filter([
            $this->city,
            trim(($this->state ?? '') . ' ' . ($this->zip_code ?? '')),
        ])));

        return trim(implode("\n", array_filter([$line, $cityLine, $this->country])));
    }
}
