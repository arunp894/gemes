<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Blog post. Managed in admin (resources/views/blogs), displayed on the
 * public storefront at /blog and /blog/{slug} (WebsiteController).
 */
class Blog extends Model implements HasMedia
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

    public const MEDIA_COLLECTION_IMAGE = 'blog_featured_image';

    /* -----------------------------------------------------------------
     |  Fillable
     | -----------------------------------------------------------------
     */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'       => 'boolean',
        'published_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $blog) {
            if (empty($blog->slug)) {
                $blog->slug = self::generateUniqueSlug($blog->title);
            }
            // First time a post goes live, stamp published_at so the
            // front-end "latest posts" ordering reflects when it actually
            // appeared rather than when the draft row was first saved.
            if ($blog->status && ! $blog->published_at) {
                $blog->published_at = now();
            }
            if (auth()->check()) {
                $blog->created_by = $blog->created_by ?? auth()->id();
                $blog->updated_by = $blog->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $blog) {
            if ($blog->isDirty('status') && $blog->status && ! $blog->published_at) {
                $blog->published_at = now();
            }
            if (auth()->check()) {
                $blog->updated_by = auth()->id();
            }
        });
    }

    /**
     * Build a unique slug from the title, appending -2, -3, ... on
     * collision. Checked against withTrashed() so a soft-deleted post's
     * slug isn't handed out to a new one.
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i    = 1;

        while (
            self::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $i++;
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /* -----------------------------------------------------------------
     |  Media Library
     | -----------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_IMAGE)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(8)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(900)
            ->height(500)
            ->nonQueued();
    }

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

    public function getImageMediumUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE);
        return $media ? $media->getUrl('medium') : null;
    }

    public function hasImage(): bool
    {
        return $this->getFirstMedia(self::MEDIA_COLLECTION_IMAGE) !== null;
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

    /**
     * Published + already at/before now — what the public site shows.
     */
    public function scopePublished($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at')->orderByDesc('created_at');
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
        return $this->isActive() ? 'Published' : 'Draft';
    }

    public function statusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-soft-success' : 'badge-soft-secondary';
    }

    /**
     * Excerpt for cards/meta tags: the authored excerpt if set, otherwise
     * a plain-text snippet trimmed from the content.
     */
    public function displayExcerpt(int $length = 160): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return Str::limit(trim(strip_tags($this->content)), $length);
    }

    /**
     * Rough reading time in minutes, for the "5 min read" style label.
     */
    public function readingTimeMinutes(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return max(1, (int) ceil($words / 200));
    }
}
