<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value settings store.
 *
 * Rows are managed through the admin Settings page and exposed
 * to the rest of the app via App\Services\SettingService.
 *
 * @property int    $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 */
class Setting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = ['key', 'value', 'group'];

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
}
