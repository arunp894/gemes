<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Generic static-content page (About Us, Terms & Conditions, and any
 * more added later) — managed at /admin/pages, rendered publicly at
 * /pages/{slug} via WebsiteController::pageShow().
 */
class Page extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Slugs seeded by PageSeeder. Referenced from the storefront footer
     * so those links don't need the admin to have picked a slug first.
     */
    public const SLUG_ABOUT_US          = 'about-us';
    public const SLUG_TERMS_CONDITIONS  = 'terms-conditions';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'created_by',
        'updated_by',
    ];

    /* -----------------------------------------------------------------
     |  Model Events
     | -----------------------------------------------------------------
     */
    protected static function booted(): void
    {
        static::creating(function (self $page) {
            if (empty($page->slug)) {
                $page->slug = self::generateUniqueSlug($page->title);
            }
            if (auth()->check()) {
                $page->created_by = $page->created_by ?? auth()->id();
                $page->updated_by = $page->updated_by ?? auth()->id();
            }
        });

        static::updating(function (self $page) {
            if (auth()->check()) {
                $page->updated_by = auth()->id();
            }
        });
    }

    /**
     * Build a unique slug from the title, appending -2, -3, ... on
     * collision. Mirrors Blog::generateUniqueSlug().
     */
    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
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
     |  Scopes
     | -----------------------------------------------------------------
     */
    public function scopeOrdered($query)
    {
        return $query->orderByDesc('updated_at');
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
}
