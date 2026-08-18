<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Key-value settings store.
 *
 * Rows are managed through the admin Settings page and exposed
 * to the rest of the app via App\Services\SettingService.
 *
 * Branding media (logo / favicon) rides on two dedicated rows
 * (key=site_logo, key=site_favicon) via Spatie MediaLibrary rather
 * than a raw path column — consistent with every other media-bearing
 * model in the app. See SettingService::logoUrl()/faviconUrl().
 *
 * @property int    $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 */
class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'app_settings';

    protected $fillable = ['key', 'value', 'group'];

    public const MEDIA_LOGO    = 'logo';
    public const MEDIA_FAVICON = 'favicon';

    /* ---------------------------------------------------------------
     |  Helpers
     | --------------------------------------------------------------- */

    /**
     * Retrieve a single setting value (or $default if missing).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    /**
     * Upsert a single key.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );
    }

    /**
     * Return all settings as key => value array.
     */
    public static function allFlat(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    /**
     * Return all settings in a given group as key => value array.
     */
    public static function group(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /* ---------------------------------------------------------------
     |  Media Library
     | --------------------------------------------------------------- */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_LOGO)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml']);

        $this->addMediaCollection(self::MEDIA_FAVICON)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml']);
    }
}
