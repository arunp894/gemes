<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Auto-generated supplier code prefix and width.
     * Example: SUP-0001, SUP-0042, SUP-1337.
     */
    public const CODE_PREFIX = 'SUP';
    public const CODE_PAD    = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_code',
        'name',
        'company_name',
        'invoice_prefix',
        'email',
        'phone',
        'alternate_phone',
        'gst_number',
        'tax_number',
        'website',
        'country',
        'state',
        'city',
        'zip_code',
        'address',
        'opening_balance',
        'credit_limit',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'          => 'boolean',
        'opening_balance' => 'decimal:2',
        'credit_limit'    => 'decimal:2',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     |  - Auto-generate supplier_code if blank (SUP-0001 sequence).
     |  - Bind created_by / updated_by from the authenticated user.
     */
    protected static function booted(): void
    {
        static::creating(function (self $supplier) {
            if (empty($supplier->supplier_code)) {
                $supplier->supplier_code = self::generateNextCode();
            } else {
                $supplier->supplier_code = strtoupper($supplier->supplier_code);
            }

            // Auto-derive invoice_prefix when blank: strip dashes from the
            // supplier_code, uppercase, cap at 10 chars. E.g. SUP-0001 -> SUP0001.
            if (empty($supplier->invoice_prefix)) {
                $supplier->invoice_prefix = strtoupper(
                    substr(str_replace('-', '', (string) $supplier->supplier_code), 0, 10)
                );
            } else {
                $supplier->invoice_prefix = strtoupper($supplier->invoice_prefix);
            }

            if (auth()->check()) {
                $supplier->created_by = $supplier->created_by ?? auth()->id();
                $supplier->updated_by = $supplier->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $supplier) {
            if (auth()->check()) {
                $supplier->updated_by = auth()->id();
            }
        });
    }

    /**
     * Build the next sequential supplier code, e.g. SUP-0001.
     * Falls back gracefully if the highest code is non-numeric.
     */
    public static function generateNextCode(): string
    {
        $prefix = self::CODE_PREFIX . '-';

        // Pull every non-deleted supplier_code that follows the prefix pattern
        // and resolve the largest trailing integer. Using withTrashed() so we
        // don't recycle codes from soft-deleted rows.
        $max = self::withTrashed()
            ->where('supplier_code', 'like', $prefix . '%')
            ->get(['supplier_code'])
            ->map(function ($row) use ($prefix) {
                $tail = substr($row->supplier_code, strlen($prefix));
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class);
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

    /**
     * Best-effort display name: prefer company, fall back to contact name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
