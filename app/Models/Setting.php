<?php

namespace App\Models;

use App\Support\Money;
use App\Support\SettingsSchema;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'shop.settings';

    /**
     * Reads a shop setting, typed according to SettingsSchema, falling back to its default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $field = SettingsSchema::field($key);
        $stored = static::allValues();

        if (! array_key_exists($key, $stored) || $stored[$key] === null) {
            return $field['default'] ?? $default;
        }

        return static::cast($stored[$key], $field['type'] ?? 'text');
    }

    /**
     * Saves several settings at once and clears the cache. Arrays are stored as JSON.
     *
     * @param  array<string, mixed>  $values
     */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : (is_bool($value) ? (int) $value : $value)],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Replaces {placeholders} with current settings, e.g. "Retours sous {return_days} jours".
     */
    public static function interpolate(?string $text): string
    {
        if ($text === null || ! str_contains($text, '{')) {
            return (string) $text;
        }

        return (string) preg_replace_callback('/\{([a-z_]+)\}/', function (array $match): string {
            return match ($match[1]) {
                'free_shipping_threshold', 'shipping_fee' => Money::format((float) static::get($match[1])),
                default => SettingsSchema::field($match[1]) ? (string) static::get($match[1]) : $match[0],
            };
        }, $text);
    }

    /**
     * Public URL of an image setting: bundled file (img/...) or uploaded file on the public disk.
     */
    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Str::startsWith($path, 'img/') ? asset($path) : Storage::disk('public')->url($path);
    }

    /**
     * Turns a relative link from the settings ("/shop?cat=x") into a full URL.
     */
    public static function link(?string $url): string
    {
        $url = trim((string) $url);

        return $url === '' || Str::startsWith($url, ['http://', 'https://', 'mailto:', 'tel:', '#']) ? ($url ?: '#') : url($url);
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

    private static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'list', 'checkboxes' => json_decode($value, true) ?: [],
            'boolean' => (bool) (int) $value,
            'number' => str_contains($value, '.') ? (float) $value : (int) $value,
            default => $value,
        };
    }
}
