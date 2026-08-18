<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lookup list for gemstone country of origin (e.g. Mozambique, Tanzania,
 * Brazil). Selected on a purchase line and stamped onto the product(s)
 * it creates — see PurchaseService::syncLines(). Also editable directly
 * on the product form.
 */
class CountryOfOrigin extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Eloquent's naive pluralization would guess 'country_of_origins'
     * (it only pluralizes the last word of the class name) — the actual
     * table uses the correct English plural instead.
     */
    protected $table = 'countries_of_origin';

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'display_order',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'        => 'boolean',
        'display_order' => 'integer',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $origin) {
            if (auth()->check()) {
                $origin->created_by = $origin->created_by ?? auth()->id();
                $origin->updated_by = $origin->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $origin) {
            if (auth()->check()) {
                $origin->updated_by = auth()->id();
            }
        });
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
        return $query->orderBy('display_order', 'asc')->orderBy('name', 'asc');
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    /**
     * True when at least one purchase line or product (including
     * soft-deleted) references this origin. Informational only — deleting
     * an in-use origin is still allowed (soft delete; the FK is
     * nullOnDelete), this just powers a confirmation warning in the UI.
     */
    public function isInUse(): bool
    {
        return $this->purchaseLines()->withTrashed()->exists()
            || $this->products()->withTrashed()->exists();
    }
}
