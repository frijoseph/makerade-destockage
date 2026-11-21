<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'shop.settings';

    /**
     * Reads a shop setting, falling back to config/shop.php when it was never saved.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = static::allValues();

        return $stored[$key] ?? config("shop.{$key}", $default);
    }

    /**
     * Saves several settings at once and clears the cache.
     *
     * @param  array<string, scalar|null>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    public static function allValues(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return static::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            return [];
        }
    }
}
