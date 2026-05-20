<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * Status constants (mirrors Category's boolean convention).
     * 0 = Draft (not published anywhere), 1 = Active (eligible for listing).
     */
    public const STATUS_DRAFT  = 0;
    public const STATUS_ACTIVE = 1;

    /**
     * Treatment options for gemstone products (spec §4.2).
     *
     * @var array<int, string>
     */
    public const TREATMENTS = [
        'None',
        'Heat',
        'Fracture Filled',
        'Beryllium',
        'Glass Filled',
        'Unknown',
    ];

    /**
     * Clarity grade options (spec §4.2).
     *
     * @var array<int, string>
     */
    public const CLARITY_GRADES = [
        'IF', 'VVS1', 'VVS2', 'VS1', 'VS2', 'SI1', 'SI2', 'I1', 'I2', 'I3',
    ];

    /**
     * Cut / shape options (spec §4.2).
     *
     * @var array<int, string>
     */
    public const CUT_SHAPES = [
        'Round', 'Oval', 'Cushion', 'Emerald Cut', 'Pear', 'Marquise', 'Other',
    ];

    /**
     * Stone type options (spec §4.2).
     *
     * @var array<int, string>
     */
    public const STONE_TYPES = [
        'Ruby', 'Sapphire', 'Emerald', 'Diamond', 'Opal', 'Other',
    ];

    /**
     * Top-level category codes that flag a product as a "gemstone" product.
     * Used by isGemstone() to decide whether gemstone fields are required.
     * Adjust seeder accordingly if codes differ.
     *
     * @var array<int, string>
     */
    public const GEMSTONE_PARENT_CODES = ['GEM', 'CERT'];

    /**
     * Media collection names.
     */
    public const MEDIA_COLLECTION_PRIMARY     = 'primary_image';
    public const MEDIA_COLLECTION_GALLERY     = 'gallery_images';
    public const MEDIA_COLLECTION_CERTIFICATE = 'certificate_image';

    /**
     * Maximum gallery images per product (spec §4.1).
     */
    public const MAX_GALLERY_IMAGES = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Core
        'title',
        'sku',
        'category_id',
        'short_description',
        'full_description',
        'country_of_origin',
        'notes_tags',
        'status',
        // Gemstone-specific
        'carat_weight',
        'stone_type',
        'colour_grade',
        'clarity_grade',
        'cut_shape',
        'treatment',
        'certificate_number',
        // Website visibility
        'website_enabled',
        'website_price',
        'website_title',
        'website_description',
        'featured_product',
        'website_sort_order',
        'website_enabled_at',
        'website_disabled_at',
        // System
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status'              => 'boolean',
        'category_id'         => 'integer',
        'carat_weight'        => 'decimal:3',
        'website_enabled'     => 'boolean',
        'website_price'       => 'decimal:2',
        'featured_product'    => 'boolean',
        'website_sort_order'  => 'integer',
        'website_enabled_at'  => 'datetime',
        'website_disabled_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $product) {
            if (auth()->check()) {
                $product->created_by = $product->created_by ?? auth()->id();
                $product->updated_by = $product->updated_by ?? auth()->id();
            }

            // If the product is being created with website_enabled = true,
            // stamp the enabled_at timestamp.
            if ($product->website_enabled && ! $product->website_enabled_at) {
                $product->website_enabled_at = now();
            }
        });

        static::updating(function (self $product) {
            if (auth()->check()) {
                $product->updated_by = auth()->id();
            }

            // Maintain website_enabled_at / website_disabled_at timestamps
            // whenever the toggle is flipped.
            if ($product->isDirty('website_enabled')) {
                if ($product->website_enabled) {
                    $product->website_enabled_at = now();
                } else {
                    $product->website_disabled_at = now();
                    // Per spec §6 business rule: clear the featured flag when
                    // the product is disabled from the website.
                    $product->featured_product = false;
                }
            }
        });
    }

    /* -----------------------------------------------------------------
     |  Media Library
     | -----------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        // Primary product image — required for website enablement (spec §6).
        $this->addMediaCollection(self::MEDIA_COLLECTION_PRIMARY)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        // Gallery — up to 10 additional images (enforced at app level).
        $this->addMediaCollection(self::MEDIA_COLLECTION_GALLERY)
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        // Certificate — optional, accepts PDF or image (spec §4.2).
        $this->addMediaCollection(self::MEDIA_COLLECTION_CERTIFICATE)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(120)
            ->height(120)
            ->sharpen(8)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }

    /* -----------------------------------------------------------------
     |  Accessors
     | -----------------------------------------------------------------
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_PRIMARY);
        return $media ? $media->getUrl() : null;
    }

    public function getPrimaryThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_PRIMARY);
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getGalleryUrlsAttribute(): array
    {
        return $this->getMedia(self::MEDIA_COLLECTION_GALLERY)
            ->map(fn ($m) => [
                'id'    => $m->id,
                'url'   => $m->getUrl(),
                'thumb' => $m->getUrl('thumb'),
            ])
            ->toArray();
    }

    public function getCertificateUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_CERTIFICATE);
        return $media ? $media->getUrl() : null;
    }

    /**
     * Title actually shown on the website
     * (override if set, otherwise the main title).
     */
    public function getDisplayWebsiteTitleAttribute(): string
    {
        return $this->website_title ?: $this->title;
    }

    /**
     * Description actually shown on the website
     * (override if set, otherwise the full description).
     */
    public function getDisplayWebsiteDescriptionAttribute(): ?string
    {
        return $this->website_description ?: $this->full_description;
    }

    /* -----------------------------------------------------------------
     |  Scopes
     | -----------------------------------------------------------------
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeWebsiteEnabled($query)
    {
        return $query->where('website_enabled', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured_product', true);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: products whose category's TOP-LEVEL parent code is in the
     * gemstone group. Uses a whereHas through the self-referential
     * categories relationship.
     */
    public function scopeGemstones($query)
    {
        return $query->whereHas('category.parent', function ($q) {
            $q->whereIn('code', self::GEMSTONE_PARENT_CODES);
        });
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class);
    }

    public function primaryBarcode(): HasOne
    {
        return $this->hasOne(Barcode::class)->where('is_primary', true);
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

    public function isDraft(): bool
    {
        return ! $this->isActive();
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Active' : 'Draft';
    }

    public function statusBadgeClass(): string
    {
        return $this->isActive() ? 'badge bg-success' : 'badge bg-warning';
    }

    public function isWebsiteEnabled(): bool
    {
        return (bool) $this->website_enabled === true;
    }

    public function websiteVisibilityLabel(): string
    {
        return $this->isWebsiteEnabled() ? 'Enabled' : 'Disabled';
    }

    public function websiteBadgeClass(): string
    {
        return $this->isWebsiteEnabled() ? 'badge bg-info' : 'badge bg-secondary';
    }

    /**
     * Returns true if this product belongs to a gemstone-family category.
     * Walks up the category hierarchy: if the category itself is top-level
     * use its own code, otherwise use the parent's code.
     */
    public function isGemstone(): bool
    {
        $this->loadMissing('category.parent');

        $category = $this->category;
        if (! $category) {
            return false;
        }

        $top = $category->parent ?? $category;

        return in_array(strtoupper((string) $top->code), self::GEMSTONE_PARENT_CODES, true);
    }

    /**
     * Returns true if the product has a primary image uploaded.
     * Required before website enablement per spec §6 business rules.
     */
    public function hasPrimaryImage(): bool
    {
        return $this->getFirstMedia(self::MEDIA_COLLECTION_PRIMARY) !== null;
    }

    /**
     * Returns the top-level category (the "Category" in the spec's
     * Category → Subcategory → Product hierarchy).
     */
    public function getTopCategoryAttribute(): ?Category
    {
        $this->loadMissing('category.parent');
        return $this->category?->parent ?? $this->category;
    }
}
