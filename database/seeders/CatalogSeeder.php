<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\StockMovementType;
use App\Support\Catalog;
use App\Support\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Loads the bundled catalogue (app/Support/Catalog.php) into MySQL. Safe to run several times.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Catalog::seedCategories() as $position => $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category + ['position' => $position]);
        }

        foreach (Catalog::seedBrands() as $brand) {
            Brand::query()->updateOrCreate(['slug' => Str::slug($brand['name'])], $brand);
        }

        $categoryIds = Category::query()->pluck('id', 'slug');

        foreach (Catalog::seedProducts() as $index => $product) {
            $brand = Brand::query()->firstOrCreate(
                ['slug' => Str::slug($product['brand'])],
                ['name' => $product['brand'], 'color' => $product['c1']],
            );

            $model = Product::query()->updateOrCreate(['slug' => $product['slug']], [
                'category_id' => $categoryIds[$product['category']],
                'brand_id' => $brand->id,
                'sku' => mb_strtoupper((string) Setting::get('sku_prefix')).'-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                'name' => $product['name'],
                'variant' => $product['variant'],
                'price' => $product['price'],
                'old_price' => $product['old_price'],
                'cost_price' => round($product['price'] * 0.62, 2),
                'low_stock_threshold' => 15,
                'rating' => $product['rating'],
                'reviews' => $product['reviews'],
                'kind' => $product['kind'],
                'c1' => $product['c1'],
                'c2' => $product['c2'],
                'label' => $product['label'],
                'is_new' => $product['is_new'],
                'is_hot' => $product['hot'],
                'is_active' => true,
            ]);

            if ($model->wasRecentlyCreated) {
                Inventory::move($model, StockMovementType::Restock, 60 + crc32($product['slug']) % 140, 'Stock initial');
            }
        }
    }
}
