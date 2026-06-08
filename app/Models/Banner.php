<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Banner extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    /* -----------------------------------------------------------------
     |  Constants
     | -----------------------------------------------------------------
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Positions where a banner can be displayed.
     * Adding a new position? Append here and to the FormRequest rules.
     */
    public const POSITIONS = [
        'home'     => 'Home Page',
        'category' => 'Category Page',
        'product'  => 'Product Page',
        'promo'    => 'Promotional',
    ];

    public const MEDIA_COLLECTION_IMAGE = 'banner_image';

    /* -----------------------------------------------------------------
     |  Fillable
     | -----------------------------------------------------------------
     */
    protected $fillable = [
        'title',
        'subtitle',
        'link_url',
        'link_text',
        'position',
        'sort_order',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'     => 'boolean',
        'sort_order' => 'integer',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $banner) {
            if (auth()->check()) {
                $banner->created_by = $banner->created_by ?? auth()->id();
                $banner->updated_by = $banner->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $banner) {
            if (auth()->check()) {
                $banner->updated_by = auth()->id();
            }
        });
    }

    /* -----------------------------------------------------------------
     |  Media Library
     | -----------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(150)
            ->sharpen(8)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(900)
            ->height(400)
            ->nonQueued();
    }

    /* -----------------------------------------------------------------
     |  Accessors
     | -----------------------------------------------------------------
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);
        return $media ? $media->getUrl() : null;
    }

    public function getImageThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);
        return $media ? $media->getUrl('thumb') : null;
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
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    public function scopeForPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Banners that are currently live (active + within date window).
     */
    public function scopeLive($query)
    {
        $now = now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
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

    public function positionLabel(): string
    {
        return self::POSITIONS[$this->position] ?? ucfirst((string) $this->position);
    }

    public function positionBadgeClass(): string
    {
        return match ($this->position) {
            'home'     => 'badge-soft-primary',
            'category' => 'badge-soft-info',
            'product'  => 'badge-soft-success',
            'promo'    => 'badge-soft-warning',
            default    => 'badge-soft-secondary',
        };
    }

    public function hasImage(): bool
    {
        return $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE) !== null;
    }

    /**
     * Is this banner currently live (active + date window OK)?
     */
    public function isLive(): bool
    {
        if (! $this->isActive()) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }
        return true;
    }

    public function liveBadge(): string
    {
        return $this->isLive()
            ? '<span class="badge badge-soft-success fs-xxs">Live</span>'
            : '<span class="badge badge-soft-secondary fs-xxs">Not Live</span>';
    }
}
