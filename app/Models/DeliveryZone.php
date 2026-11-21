<?php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Throwable;

#[Fillable(['name', 'fee', 'free_threshold', 'delay', 'position', 'is_active'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'free_threshold' => 'decimal:2',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<DeliveryZone>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }

    /**
     * The active zone matching a city typed by the customer (case-insensitive), if any.
     */
    public static function forCity(?string $city): ?self
    {
        $city = mb_strtolower(trim((string) $city));

        if ($city === '') {
            return null;
        }

        try {
            return static::query()->active()->get()->first(fn (self $zone): bool => mb_strtolower($zone->name) === $city);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Zones for the storefront scripts and the checkout city list.
     *
     * @return list<array{name: string, fee: float, free_threshold: float, delay: string|null}>
     */
    public static function forStorefront(): array
    {
        try {
            return static::query()->active()->get()->map(fn (self $zone): array => [
                'name' => $zone->name,
                'fee' => $zone->fee !== null ? (float) $zone->fee : (float) Setting::get('shipping_fee'),
                'free_threshold' => $zone->free_threshold !== null ? (float) $zone->free_threshold : (float) Setting::get('free_shipping_threshold'),
                'delay' => $zone->delay ?: (string) Setting::get('delivery_delay'),
            ])->all();
        } catch (Throwable) {
            return [];
        }
    }
}
