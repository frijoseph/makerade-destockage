<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Source unique du catalogue.
 * - Lit MySQL (produits actifs uniquement) quand CATALOG_USE_DB=true et que les tables sont remplies.
 * - Sinon, utilise les données intégrées (le site fonctionne dès l'installation, et l'export statique aussi).
 */
class Catalog
{
    public static function categories(): array
    {
        return once(fn (): array => self::fromDb('categories', fn (): array => Category::query()->active()
            ->orderBy('position')->orderBy('id')->get()
            ->map(fn (Category $category): array => $category->toCatalogArray())->all())
            ?? array_map(fn (array $category): array => ['image' => Setting::imageUrl($category['image'])] + $category, self::seedCategories()));
    }

    public static function brands(): array
    {
        return once(fn (): array => self::fromDb('brands', fn (): array => Brand::query()->active()
            ->orderBy('position')->orderBy('id')->get()
            ->map(fn (Brand $brand): array => $brand->toCatalogArray())->all()) ?? self::seedBrands());
    }

    /**
     * Brands shown in the home trust band: featured ones only, capped by the settings.
     *
     * @return list<array<string, mixed>>
     */
    public static function featuredBrands(): array
    {
        $brands = array_values(array_filter(self::brands(), fn (array $brand): bool => $brand['featured'] ?? true));
        $limit = (int) Setting::get('brands_limit');

        return $limit > 0 ? array_slice($brands, 0, $limit) : $brands;
    }

    public static function products(): array
    {
        return once(fn (): array => self::fromDb('products', fn (): array => Product::query()->active()
            ->with(['brand', 'category', 'images', 'variants'])
            ->withSum(['orderItems as sold' => fn ($query) => $query->whereHas('order', fn ($query) => $query->billable())], 'quantity')
            ->orderBy('id')->get()
            ->map(fn (Product $product): array => $product->toCatalogArray())->all()) ?? self::withImage(self::seedProducts()));
    }

    /**
     * Catalogue embedded in every page for the cart scripts, keyed by cart key: the product slug, plus
     * one virtual entry « slug~variantId » per variant (own price, stock, picture, label).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function clientCatalog(): array
    {
        $catalog = [];

        foreach (self::products() as $product) {
            $catalog[$product['slug']] = $product;

            foreach ($product['variants'] ?? [] as $variant) {
                $catalog[$product['slug'].'~'.$variant['id']] = array_merge($product, [
                    'base' => $product['slug'],
                    'virtual' => true,
                    'variant' => trim($product['variant'].' · '.$variant['name'], ' ·'),
                    'price' => $variant['price'],
                    'old_price' => $variant['old_price'],
                    'off' => $variant['old_price'] ? (int) round((1 - $variant['price'] / $variant['old_price']) * 100) : 0,
                    'stock' => $variant['stock'],
                    'image' => $variant['image'] ?: ($product['image'] ?? null),
                ]);
                unset($catalog[$product['slug'].'~'.$variant['id']]['variants'], $catalog[$product['slug'].'~'.$variant['id']]['images']);
            }
        }

        return $catalog;
    }

    public static function product(string $slug): ?array
    {
        foreach (self::products() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }

        return null;
    }

    public static function deals(): array
    {
        return array_values(array_filter(self::products(), fn ($p) => $p['off'] > 0));
    }

    /**
     * Highest real discount of a category ("all" for the whole shop), used by "auto:" badges.
     */
    public static function bestDiscount(string $category = 'all'): int
    {
        return (int) collect(self::products())
            ->when($category !== 'all', fn ($products) => $products->where('category', $category))
            ->max('off');
    }

    /**
     * Resolves a badge setting: free text, or "auto:category" for the best real discount.
     *
     * @return array{type: string, value: string}|null
     */
    public static function badge(?string $badge): ?array
    {
        $badge = trim((string) $badge);

        if ($badge === '') {
            return null;
        }

        if (str_starts_with($badge, 'auto:')) {
            $discount = self::bestDiscount(substr($badge, 5));

            return $discount > 0 ? ['type' => 'discount', 'value' => $discount.' %'] : null;
        }

        return ['type' => 'text', 'value' => Setting::interpolate($badge)];
    }

    /**
     * Real figures for the homepage stats.
     */
    public static function stat(string $source, mixed $fixed = null): int
    {
        if ($source === 'fixed') {
            return (int) $fixed;
        }

        if (! config('shop.catalog_from_db')) {
            return match ($source) {
                'products' => count(self::products()),
                'brands' => count(self::brands()),
                default => 0,
            };
        }

        try {
            return match ($source) {
                'customers' => Customer::query()->count(),
                'products' => Product::query()->active()->count(),
                'brands' => Brand::query()->active()->count(),
                'orders' => Order::query()->where('status', OrderStatus::Delivered)->count(),
                default => 0,
            };
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Latest real purchases for the "recently bought" popup: city and product only, never the customer's name.
     *
     * @return list<array{city: string, slug: string, minutes: int}>
     */
    public static function recentSales(): array
    {
        if (! config('shop.catalog_from_db') || ! Setting::get('recent_sales_enabled')) {
            return [];
        }

        try {
            return OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('products.is_active', true)
                ->where('orders.status', '!=', OrderStatus::Cancelled->value)
                ->where('orders.placed_at', '>=', now()->subDays((int) Setting::get('recent_sales_days')))
                ->latest('orders.placed_at')
                ->limit(20)
                ->get(['orders.shipping_city', 'orders.placed_at', 'products.slug'])
                ->map(fn ($row): array => [
                    'city' => (string) ($row->shipping_city ?: Setting::get('delivery_zone')),
                    'slug' => $row->slug,
                    'minutes' => (int) Carbon::parse($row->placed_at)->diffInMinutes(now()),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Runs a catalogue query against MySQL; null means "use the bundled data".
     *
     * @param  callable(): array<int, array<string, mixed>>  $query
     * @return array<int, array<string, mixed>>|null
     */
    private static function fromDb(string $table, callable $query): ?array
    {
        if (! config('shop.catalog_from_db')) {
            return null;
        }

        try {
            if (! Schema::hasTable($table)) {
                return null;
            }

            return $query() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function seedCategories(): array
    {
        return [
            ['slug' => 'entretien', 'name' => 'Entretien ménager',       'icon' => 'leaf', 'bg' => '#fdf1d6', 'image' => 'img/clean/cat-1.jpg'],
            ['slug' => 'lessive',   'name' => 'Lessives & linge',        'icon' => 'leaf', 'bg' => '#e8eef8', 'image' => 'img/clean/cat-2.jpg'],
            ['slug' => 'cuisine',   'name' => 'Cuisine & vaisselle',     'icon' => 'leaf', 'bg' => '#fbf1df', 'image' => 'img/clean/cat-3.jpg'],
            ['slug' => 'maison',    'name' => 'Maison & rangement',      'icon' => 'leaf', 'bg' => '#e7eef2', 'image' => 'img/clean/cat-4.jpg'],
            ['slug' => 'bain',      'name' => 'Salle de bain',           'icon' => 'leaf', 'bg' => '#f3f1ee', 'image' => 'img/clean/cat-5.jpg'],
            ['slug' => 'hygiene',   'name' => 'Hygiène & désinfection',  'icon' => 'leaf', 'bg' => '#e6f2ea', 'image' => 'img/clean/cat-6.jpg'],
            ['slug' => 'soins',     'name' => 'Soins personnels',        'icon' => 'leaf', 'bg' => '#f1f1f1', 'image' => 'img/clean/cat-7.jpg'],
            ['slug' => 'bienetre',  'name' => 'Bien-être & cheveux',     'icon' => 'leaf', 'bg' => '#f3ece3', 'image' => 'img/clean/cat-8.jpg'],
        ];
    }

    public static function seedBrands(): array
    {
        return [
            ['name' => 'Dettol',     'color' => '#2e9b4a', 'style' => 'pill'],
            ['name' => 'Colgate',    'color' => '#e3262e', 'style' => 'script'],
            ['name' => 'Surf excel', 'color' => '#1c4fa3', 'style' => 'swash'],
            ['name' => 'Pears',      'color' => '#2a2a2a', 'style' => 'serif'],
            ['name' => 'Harpic',     'color' => '#c8202f', 'style' => 'swash'],
            ['name' => 'Vim',        'color' => '#7cb82f', 'style' => 'circle'],
            ['name' => 'Closeup',    'color' => '#d4262d', 'style' => 'plain'],
            ['name' => 'Cinthol',    'color' => '#3b3b3b', 'style' => 'plain'],
        ];
    }

    public static function seedProducts(): array
    {
        $p = function ($slug, $name, $variant, $brand, $cat, $price, $old, $rating, $reviews, $kind, $c1, $c2, $label, $extra = []) {
            return array_merge([
                'slug' => $slug, 'name' => $name, 'variant' => $variant, 'brand' => $brand,
                'category' => $cat, 'price' => $price, 'old_price' => $old,
                'off' => $old ? (int) round((1 - $price / $old) * 100) : 0,
                'rating' => $rating, 'reviews' => $reviews, 'kind' => $kind,
                'c1' => $c1, 'c2' => $c2, 'label' => $label, 'is_new' => false, 'hot' => false,
            ], $extra);
        };

        return [
            $p('harpic-power-plus', 'Harpic Power Plus', 'Nettoyant WC 1 L', 'Harpic', 'entretien', 3.74, 4.99, 4.5, 2568, 'bottle', '#2358b8', '#e9302f', 'HARPIC', ['hot' => true, 'off' => 20]),
            $p('surf-excel-easy-wash', 'Surf Excel Easy Wash', 'Lessive en poudre 1 kg', 'Surf Excel', 'lessive', 6.39, 7.99, 4.5, 3245, 'pack', '#1f4fb0', '#f2c230', 'Surf excel'),
            $p('dettol-original', 'Dettol Original', 'Antiseptique liquide 550 ml', 'Dettol', 'hygiene', 6.79, 7.99, 4.5, 4125, 'bottle', '#d77a1d', '#2e9b4a', 'Dettol'),
            $p('colgate-max-fresh', 'Colgate Max Fresh', 'Dentifrice fraîcheur 150 g', 'Colgate', 'soins', 2.09, 2.99, 4.5, 6214, 'tube', '#d62a35', '#1f5bb5', 'Colgate'),
            $p('pears-soft-fresh', 'Pears Soft & Fresh', 'Savon de toilette 125 g', 'Pears', 'bain', 1.27, 1.59, 4.5, 1867, 'box', '#3b93d6', '#cfe8f7', 'Pears'),
            $p('cinthol-lime', 'Cinthol Lime', 'Savon déodorant 100 g', 'Cinthol', 'bain', 0.74, 0.99, 4.5, 2468, 'soap', '#7cb82f', '#4f8a1c', 'CINTHOL', ['off' => 20]),
            $p('dove-beauty-cream', 'Dove Beauty Cream Bar', 'Savon crème 2 × 90 g', 'Dove', 'bain', 2.49, 2.99, 4.5, 3120, 'box', '#2a4a8c', '#ffffff', 'Dove'),
            $p('vim-lemon-gel', 'Vim Lemon', 'Recharge vaisselle 500 ml', 'Vim', 'cuisine', 2.49, 2.99, 4.0, 1320, 'pack', '#8ac42b', '#f4d21a', 'VIM', ['is_new' => true]),
            $p('hs-supreme', 'Head & Shoulders Supreme', 'Shampooing antipelliculaire 300 ml', 'Head & Shoulders', 'bienetre', 5.99, 7.49, 4.5, 1540, 'bottle', '#1d8fb0', '#e0b04a', 'H&S', ['is_new' => true]),
            $p('colgate-total', 'Colgate Total', 'Protection complète 100 ml', 'Colgate', 'soins', 3.19, 3.99, 4.5, 3300, 'tube', '#c91f2e', '#f1f1f1', 'Colgate'),
            $p('dettol-handwash', 'Dettol Cleanse', 'Savon liquide mains 250 ml', 'Dettol', 'hygiene', 4.29, 4.99, 4.5, 2210, 'bottle', '#2e9b4a', '#ffffff', 'Dettol', ['is_new' => true]),
            $p('surf-matic', 'Surf Excel Aloe Vera', 'Lessive en poudre 800 g', 'Surf Excel', 'lessive', 8.99, 10.99, 4.5, 1890, 'pack', '#1f4fb0', '#f2c230', 'Surf excel'),
            $p('palmolive-vaisselle', 'Palmolive Original', 'Liquide vaisselle 750 ml', 'Palmolive', 'cuisine', 2.99, 3.49, 4.0, 612, 'bottle', '#3aa84a', '#f2dc2b', 'Palmolive', ['is_new' => true]),
            $p('palmolive-shampoo', 'Palmolive Naturals', 'Shampooing 350 ml', 'Palmolive', 'bienetre', 3.49, 4.29, 4.0, 904, 'bottle', '#4aa032', '#ffffff', 'Palmolive'),
            $p('cinthol-original', 'Cinthol Original', 'Savon de bain 100 g', 'Cinthol', 'bain', 0.89, null, 4.0, 410, 'soap', '#d62a35', '#9b1a22', 'CINTHOL', ['is_new' => true]),
            $p('dove-pink', 'Dove Pink Rosa', 'Savon crème 2 × 90 g', 'Dove', 'bain', 2.59, 2.99, 4.5, 1780, 'box', '#e88aa0', '#ffffff', 'Dove', ['is_new' => true]),
            $p('closeup-red-hot', 'Closeup Red Hot', 'Dentifrice gel 120 g', 'Closeup', 'soins', 1.89, 2.39, 4.0, 980, 'tube', '#e02b2f', '#ffffff', 'closeup'),
        ];
    }

    /** Ajoute l'URL de la photo réelle si le fichier existe dans public/img/products. */
    private static function withImage(array $rows): array
    {
        return array_map(function ($p) {
            if (isset($p['slug']) && is_file(public_path("img/products/{$p['slug']}.jpg"))) {
                $p['image'] = asset("img/products/{$p['slug']}.jpg");
            }

            return $p;
        }, $rows);
    }
}
