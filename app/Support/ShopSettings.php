<?php

namespace App\Support;

use App\Models\DeliveryZone;
use App\Models\Popup;
use App\Models\Setting;
use App\PaymentMethod;
use Illuminate\Support\Facades\Route;

/**
 * Read-only access to the shop settings from Blade, shared with every view as $shop.
 *
 *   {{ $shop->contact_phone }}            raw value
 *   {{ $shop->text('why_title') }}        value with {placeholders} resolved
 *   {{ $shop->money($price) }}            amount in the shop currency
 */
class ShopSettings
{
    public function __get(string $key): mixed
    {
        return Setting::get($key);
    }

    public function __isset(string $key): bool
    {
        return SettingsSchema::field($key) !== null;
    }

    /**
     * Public configuration of the chat widget (null when the assistant is off). Never includes the API key.
     *
     * @return array<string, mixed>|null
     */
    public function assistantConfig(): ?array
    {
        if (! Setting::get('ai_enabled')) {
            return null;
        }

        return [
            'endpoint' => route('assistant.message'),
            'name' => Setting::interpolate((string) Setting::get('ai_name')),
            'title' => Setting::interpolate((string) Setting::get('ai_title')),
            'avatar' => Setting::imageUrl(Setting::get('ai_avatar')),
            'label' => Setting::interpolate((string) Setting::get('ai_launcher_label')),
            'position' => Setting::get('ai_position') === 'left' ? 'left' : 'right',
            'welcome' => Setting::interpolate((string) Setting::get('ai_welcome')),
            'suggestions' => collect((array) Setting::get('ai_suggestions'))->pluck('text')->filter()->values()->all(),
            'handoff' => Setting::get('ai_handoff_enabled') && Setting::get('whatsapp_number')
                ? ['label' => Setting::interpolate((string) Setting::get('ai_handoff_label')), 'url' => 'https://wa.me/'.Setting::get('whatsapp_number').'?text='.rawurlencode(Setting::interpolate((string) Setting::get('whatsapp_message')))]
                : null,
        ];
    }

    /**
     * CSS variables derived from the primary colour, injected after the stylesheets.
     */
    public function primaryCss(): string
    {
        $color = (string) Setting::get('theme_color');

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return '';
        }

        return ":root{--g:{$color};--g-d:color-mix(in srgb,{$color} 78%,#000);--g-2:color-mix(in srgb,{$color} 88%,#fff);--g-l:color-mix(in srgb,{$color} 12%,#fff);--g-xl:color-mix(in srgb,{$color} 6%,#fff);--primary:{$color}}";
    }

    public function text(string $key): string
    {
        return Setting::interpolate((string) Setting::get($key));
    }

    public function interpolate(?string $text): string
    {
        return Setting::interpolate($text);
    }

    public function image(string $key): ?string
    {
        return Setting::imageUrl(Setting::get($key));
    }

    public function imageUrl(?string $path): ?string
    {
        return Setting::imageUrl($path);
    }

    public function link(?string $url): string
    {
        return Setting::link($url);
    }

    public function money(float|int|string|null $amount): string
    {
        return Money::format($amount);
    }

    public function phone(?string $e164): string
    {
        return Phone::format($e164);
    }

    public function number(float|int $value): string
    {
        return Money::number($value);
    }

    /**
     * Payment methods offered to customers, as value => label.
     *
     * @return array<string, string>
     */
    public function paymentMethods(): array
    {
        $enabled = (array) Setting::get('payment_methods');

        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method): bool => in_array($method->value, $enabled, true))
            ->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])
            ->all();
    }

    /**
     * Social networks that have a URL, as network => url.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return collect(['facebook', 'instagram', 'tiktok', 'youtube', 'pinterest'])
            ->mapWithKeys(fn (string $network): array => [$network => (string) Setting::get("social_{$network}")])
            ->filter()
            ->all();
    }

    /**
     * Footer columns with their links resolved ("Libellé | /url" per line, "catégories" = active categories).
     *
     * @return list<array{title: string, links: list<array{label: string, url: string}>}>
     */
    public function footerColumns(): array
    {
        return collect((array) Setting::get('footer_columns'))->map(function (array $column): array {
            return ['title' => Setting::interpolate($column['title'] ?? ''), 'links' => $this->parseLinks((string) ($column['links'] ?? ''))];
        })->all();
    }

    /**
     * One link per line, « Libellé | /adresse »; the word « catégories » alone lists the active categories.
     *
     * @return list<array{label: string, url: string}>
     */
    private function parseLinks(string $text): array
    {
        return collect(preg_split('/\R/', $text))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->flatMap(function (string $line): array {
                if (mb_strtolower($line) === 'catégories') {
                    return array_map(fn (array $category): array => ['label' => $category['name'], 'url' => route('shop', ['cat' => $category['slug']])], Catalog::categories());
                }

                [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

                return [['label' => Setting::interpolate($label), 'url' => Setting::link($url)]];
            })
            ->values()
            ->all();
    }

    /**
     * Header menu: configured links, or the active categories when none are set.
     *
     * @return list<array{label: string, url: string, children: list<array{label: string, url: string}>}>
     */
    public function headerMenu(): array
    {
        $links = collect((array) Setting::get('header_menu'))
            ->filter(fn (array $link): bool => ($link['label'] ?? '') !== '')
            ->map(fn (array $link): array => [
                'label' => Setting::interpolate($link['label']),
                'url' => Setting::link($link['url'] ?? ''),
                'children' => $this->parseLinks((string) ($link['children'] ?? '')),
            ])
            ->values()
            ->all();

        if ($links === []) {
            $links = $this->automaticMenu();
        }

        if (Setting::get('all_products_link_enabled') && ($label = trim((string) Setting::get('all_products_link_label'))) !== '') {
            array_unshift($links, ['label' => Setting::interpolate($label), 'url' => route('shop'), 'children' => []]);
        }

        return $links;
    }

    /**
     * Without a custom menu: the first categories, the others grouped under a « Plus » sub-menu so the bar never overflows.
     *
     * @return list<array{label: string, url: string, children: list<array{label: string, url: string}>}>
     */
    private function automaticMenu(): array
    {
        $items = array_map(fn (array $category): array => ['label' => $category['name'], 'url' => route('shop', ['cat' => $category['slug']]), 'children' => []], Catalog::categories());
        $limit = (int) Setting::get('header_menu_auto_limit');

        if ($limit <= 0 || count($items) <= $limit) {
            return $items;
        }

        $more = array_map(fn (array $item): array => ['label' => $item['label'], 'url' => $item['url']], array_slice($items, $limit));
        $items = array_slice($items, 0, $limit);
        $items[] = ['label' => Setting::interpolate((string) Setting::get('header_more_label')) ?: 'Plus', 'url' => route('shop'), 'children' => $more];

        return $items;
    }

    /**
     * Active delivery zones (name, fee, free-shipping threshold, delay).
     *
     * @return list<array{name: string, fee: float, free_threshold: float, delay: string|null}>
     */
    public function zones(): array
    {
        return once(fn (): array => DeliveryZone::forStorefront());
    }

    /**
     * Announcement bar messages with their links resolved.
     *
     * @return list<array{icon: string, text: string, url: string}>
     */
    public function announcements(): array
    {
        if (! Setting::get('announcement_enabled')) {
            return [];
        }

        return collect((array) Setting::get('announcement_items'))
            ->filter(fn (array $item): bool => ($item['text'] ?? '') !== '')
            ->map(fn (array $item): array => [
                'icon' => $item['icon'] ?? 'tag',
                'text' => Setting::interpolate($item['text']),
                'url' => ($item['url'] ?? '') !== '' ? Setting::link($item['url']) : '',
            ])->values()->all();
    }

    /**
     * Data handed to the storefront scripts.
     *
     * @return array<string, mixed>
     */
    public function forScripts(): array
    {
        $user = auth()->user();

        return [
            'money' => Money::config(),
            'primary' => (string) Setting::get('theme_color'),
            'assistant' => $this->assistantConfig(),
            'shipping' => ['fee' => (float) Setting::get('shipping_fee'), 'freeFrom' => (float) Setting::get('free_shipping_threshold')],
            'zones' => $this->zones(),
            'phone' => [
                'default' => Phone::defaultIso(),
                'countries' => collect(Phone::countries())->map(fn (array $country, string $iso): array => ['iso' => $iso] + $country)->values()->all(),
            ],
            'recentSales' => Catalog::recentSales(),
            'popups' => Popup::forStorefront(Route::currentRouteName()),
            'confetti' => (bool) Setting::get('confetti_enabled'),
            'recentlyViewed' => (bool) Setting::get('recently_viewed_enabled'),
            'user' => $user && ! $user->canAccessAdmin() ? ['name' => $user->name, 'phone' => $user->phone, 'email' => $user->email] : null,
        ];
    }
}
