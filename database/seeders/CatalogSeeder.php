<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Catalog::seedCategories() as $r) Category::updateOrCreate(['slug' => $r['slug']], $r);
        foreach (Catalog::seedBrands() as $r) Brand::updateOrCreate(['name' => $r['name']], $r);
        $products = Catalog::seedProducts();
        foreach ($products as $r) Product::updateOrCreate(['slug' => $r['slug']], $r);
        Product::whereNotIn('slug', array_column($products, 'slug'))->delete();
        Brand::whereNotIn('name', array_column(Catalog::seedBrands(), 'name'))->delete();
    }
}
