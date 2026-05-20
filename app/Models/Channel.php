<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Channel extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Channel code constants — used throughout the app to reference
     * a known channel without hardcoding strings.
     */
    public const CODE_EBAY        = 'ebay';
    public const CODE_CATAWIKI    = 'catawiki';
    public const CODE_WEBSITE     = 'website';
    public const CODE_SUKAINAGEMS = 'sukainagems';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'icon',
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
        static::creating(function (self $channel) {
            if (auth()->check()) {
                $channel->created_by = $channel->created_by ?? auth()->id();
                $channel->updated_by = $channel->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $channel) {
            if (auth()->check()) {
                $channel->updated_by = auth()->id();
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
    public function barcodes(): BelongsToMany
    {
        return $this->belongsToMany(Barcode::class, 'barcode_channel')
            ->withTimestamps();
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
        return $this->isActive() ? 'badge bg-success' : 'badge bg-secondary';
    }
}
