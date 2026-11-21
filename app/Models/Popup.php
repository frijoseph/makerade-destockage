<?php

namespace App\Models;

use Database\Factories\PopupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

#[Fillable([
    'name', 'kind', 'title', 'body', 'image', 'coupon_code', 'button_label', 'button_url', 'trigger', 'trigger_value',
    'frequency', 'pages', 'devices', 'accent', 'segments', 'priority', 'starts_at', 'ends_at', 'is_active', 'impressions', 'clicks',
])]
class Popup extends Model
{
    /** @use HasFactory<PopupFactory> */
    use HasFactory;

    public const KINDS = ['promo' => 'Promotion', 'newsletter' => 'Newsletter', 'wheel' => 'Roue de la chance'];

    public const TRIGGERS = ['delay' => 'Après un délai (secondes)', 'scroll' => 'Après un défilement (%)', 'exit' => 'À la sortie de la page'];

    public const FREQUENCIES = ['once' => 'Une seule fois par visiteur', 'session' => 'Une fois par visite', 'daily' => 'Une fois par jour', 'always' => 'À chaque page'];

    public const PAGES = ['home' => 'Accueil', 'shop' => 'Boutique et offres', 'product' => 'Fiches produit', 'cart' => 'Panier', 'blog' => 'Blog', 'other' => 'Autres pages'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pages' => 'array',
            'segments' => 'array',
            'trigger_value' => 'integer',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'impressions' => 'integer',
            'clicks' => 'integer',
        ];
    }

    /**
     * @return HasMany<WheelSpin, $this>
     */
    public function spins(): HasMany
    {
        return $this->hasMany(WheelSpin::class);
    }

    /**
     * @param  Builder<Popup>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Maps a route name to one of the PAGES keys.
     */
    public static function pageKey(?string $routeName): string
    {
        return match ($routeName) {
            'home' => 'home',
            'shop', 'deals', 'arrivals', 'brands' => 'shop',
            'product' => 'product',
            'cart' => 'cart',
            'blogs', 'blog.show' => 'blog',
            default => 'other',
        };
    }

    public function showsOn(string $pageKey): bool
    {
        $pages = (array) $this->pages;

        return $pages === [] || in_array('all', $pages, true) || in_array($pageKey, $pages, true);
    }

    /**
     * Pop-ups eligible on a page, ready for the storefront script. Coupon codes of newsletter and wheel
     * pop-ups are never exposed here: they are revealed by the server after sign-up or a spin.
     *
     * @return list<array<string, mixed>>
     */
    public static function forStorefront(?string $routeName): array
    {
        try {
            $pageKey = static::pageKey($routeName);

            return static::query()->live()->orderByDesc('priority')->orderBy('id')->get()
                ->filter(fn (self $popup): bool => $popup->showsOn($pageKey))
                ->map(fn (self $popup): array => [
                    'id' => $popup->id,
                    'kind' => $popup->kind,
                    'title' => Setting::interpolate($popup->title),
                    'body' => Setting::interpolate($popup->body),
                    'image' => Setting::imageUrl($popup->image),
                    'code' => $popup->kind === 'promo' ? $popup->coupon_code : null,
                    'button_label' => $popup->button_label,
                    'button_url' => $popup->button_url ? Setting::link($popup->button_url) : null,
                    'trigger' => $popup->trigger,
                    'trigger_value' => $popup->trigger_value,
                    'frequency' => $popup->frequency,
                    'devices' => $popup->devices,
                    'accent' => $popup->accent,
                    'segments' => $popup->kind === 'wheel'
                        ? collect((array) $popup->segments)->map(fn (array $segment): array => ['label' => $segment['label'] ?? '', 'color' => $segment['color'] ?? '#237a32'])->values()->all()
                        : [],
                ])->values()->all();
        } catch (Throwable) {
            return [];
        }
    }
}
