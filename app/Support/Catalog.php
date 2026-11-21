<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
            ->map(fn (Category $category): array => $category->toCatalogArray())->all()) ?? self::seedCategories());
    }

    public static function brands(): array
    {
        return once(fn (): array => self::fromDb('brands', fn (): array => Brand::query()->active()
            ->orderBy('id')->get()
            ->map(fn (Brand $brand): array => $brand->toCatalogArray())->all()) ?? self::seedBrands());
    }

    public static function products(): array
    {
        return once(fn (): array => self::fromDb('products', fn (): array => Product::query()->active()
            ->with(['brand', 'category'])->orderBy('id')->get()
            ->map(fn (Product $product): array => $product->toCatalogArray())->all()) ?? self::withImage(self::seedProducts()));
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
            ['slug' => 'entretien', 'name' => 'Entretien ménager',       'icon' => 'leaf', 'bg' => '#fdf1d6'],
            ['slug' => 'lessive',   'name' => 'Lessives & linge',        'icon' => 'leaf', 'bg' => '#e8eef8'],
            ['slug' => 'cuisine',   'name' => 'Cuisine & vaisselle',     'icon' => 'leaf', 'bg' => '#fbf1df'],
            ['slug' => 'maison',    'name' => 'Maison & rangement',      'icon' => 'leaf', 'bg' => '#e7eef2'],
            ['slug' => 'bain',      'name' => 'Salle de bain',           'icon' => 'leaf', 'bg' => '#f3f1ee'],
            ['slug' => 'hygiene',   'name' => 'Hygiène & désinfection',  'icon' => 'leaf', 'bg' => '#e6f2ea'],
            ['slug' => 'soins',     'name' => 'Soins personnels',        'icon' => 'leaf', 'bg' => '#f1f1f1'],
            ['slug' => 'bienetre',  'name' => 'Bien-être & cheveux',     'icon' => 'leaf', 'bg' => '#f3ece3'],
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
