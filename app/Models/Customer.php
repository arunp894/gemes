<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Customer master — extended to implement Laravel Authenticatable so that
 * customers can register/login on the storefront via the 'customer' guard.
 *
 * Mirrors the Supplier pattern: auto-generated code (CUST-0001), audit
 * columns in booted(), SoftDeletes, badge helpers.
 *
 * Customer ↔ Sale is 1:N. A "Walk-in" customer record should be seeded
 * so the sales terminal always has a valid default to bind to.
 */
class Customer extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable;

    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    public const CODE_PREFIX = 'CUST';
    public const CODE_PAD    = 4;

    public const TYPE_RETAIL    = 'retail';
    public const TYPE_WHOLESALE = 'wholesale';
    public const TYPE_WALKIN    = 'walk_in';

    public const TYPES = [
        self::TYPE_RETAIL    => 'Retail',
        self::TYPE_WHOLESALE => 'Wholesale',
        self::TYPE_WALKIN    => 'Walk-in',
    ];

    protected $fillable = [
        'customer_code',
        'name',
        'company_name',
        'customer_type',
        'email',
        'phone',
        'alternate_phone',
        'gst_number',
        'pan_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'zip_code',
        'status',
        'notes',
        'password',
        'email_verified_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status'            => 'boolean',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /* -----------------------------------------------------------------
     |  Model events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->customer_code)) {
                $c->customer_code = self::generateNextCode();
            } else {
                $c->customer_code = strtoupper($c->customer_code);
            }

            if (auth()->check()) {
                $c->created_by = $c->created_by ?? auth()->id();
                $c->updated_by = $c->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $c) {
            if (auth()->check()) {
                $c->updated_by = auth()->id();
            }
        });
    }

    /**
     * Build next sequential customer code. Includes trashed rows so codes
     * are never recycled.
     */
    public static function generateNextCode(): string
    {
        $prefix = self::CODE_PREFIX . '-';

        $max = self::withTrashed()
            ->where('customer_code', 'like', $prefix . '%')
            ->get(['customer_code'])
            ->map(function ($row) use ($prefix) {
                $tail = substr($row->customer_code, strlen($prefix));
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
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
        return self::TYPES[$this->customer_type] ?? ucfirst((string) $this->customer_type);
    }

    public function typeBadgeClass(): string
    {
        return match ($this->customer_type) {
            self::TYPE_WHOLESALE => 'badge-soft-primary',
            self::TYPE_WALKIN    => 'badge-soft-secondary',
            default              => 'badge-soft-info',
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
