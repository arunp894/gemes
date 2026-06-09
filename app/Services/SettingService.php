<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Application Settings Service
 *
 * Provides a cached, convenient API for reading and writing
 * key-value settings stored in the `app_settings` table.
 *
 * Registered as a singleton in AppServiceProvider.
 * The cache is tagged 'app_settings' and flushed on every save.
 *
 * Usage:
 *   app(SettingService::class)->get('currency_symbol', '₹')
 *   app(SettingService::class)->all()
 *   app(SettingService::class)->save($validatedArray)
 */
class SettingService
{
    private const CACHE_KEY = 'app_settings_flat';
    private const CACHE_TTL = 3600; // 1 hour

    /* ---------------------------------------------------------------
     |  Read
     | --------------------------------------------------------------- */

    /**
     * All settings as key => value flat array (cached).
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::allFlat();
        });
    }

    /**
     * Retrieve one setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Boolean convenience — '1' / 'true' / 'yes' => true.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->get($key);
        if ($val === null) return $default;
        return in_array(strtolower((string) $val), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Return formatted price string respecting currency settings.
     * e.g.  formatPrice(1250.00)  =>  "₹1,250"  or  "1,250 USD"
     */
    public function formatPrice(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') return 'Price on Request';

        $symbol   = $this->get('currency_symbol', '₹');
        $code     = $this->get('currency_code',   'USD');
        $position = $this->get('currency_position', 'before');
        $formatted = number_format((float) $amount, 0);

        return $position === 'before'
            ? $symbol . $formatted
            : $formatted . ' ' . $code;
    }

    /* ---------------------------------------------------------------
     |  Write
     | --------------------------------------------------------------- */

    /**
     * Persist an associative array of settings and flush the cache.
     *
     * @param array<string, mixed> $data  key => value pairs
     * @param string $group               default group when key doesn't exist yet
     */
    public function save(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value !== null ? (string) $value : null, 'group' => $group],
            );
        }

        $this->flush();
    }

    /**
     * Flush the settings cache (called automatically on save).
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
