<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Category extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE   = 1;
    public const STATUS_INACTIVE = 0;

    /**
     * Media collection name for the category thumbnail image.
     * Spec: JPG/PNG, max 2 MB.
     */
    public const MEDIA_COLLECTION_IMAGE = 'category_image';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'display_order',
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
        'status'        => 'boolean',
        'display_order' => 'integer',
        'parent_id'     => 'integer',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $category) {
            if (auth()->check()) {
                $category->created_by = $category->created_by ?? auth()->id();
                $category->updated_by = $category->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $category) {
            if (auth()->check()) {
                $category->updated_by = auth()->id();
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
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(120)
            ->height(120)
            ->sharpen(8)
            ->nonQueued();
    }

    /**
     * Convenience accessor — returns the image URL or null.
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);
        return $media ? $media->getUrl() : null;
    }

    public function getThumbUrlAttribute(): ?string
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
        return $query->orderBy('display_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     |  NOTE: subcategories() and products() reference models that have
     |  not been built yet. They are wired up so other code can call them
     |  once those models exist. Counts on the index page use these.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(\App\Models\Product::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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
