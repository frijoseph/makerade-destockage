<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\Catalog;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundled_catalogue_is_used_when_the_database_is_disabled(): void
    {
        config(['shop.catalog_from_db' => false]);

        $this->assertCount(count(Catalog::seedProducts()), Catalog::products());
        $this->get('/shop')->assertOk()->assertSee('Harpic Power Plus');
    }

    public function test_storefront_reads_active_products_from_the_database(): void
    {
        config(['shop.catalog_from_db' => true]);
        $this->seed(CatalogSeeder::class);
        Product::query()->where('slug', 'harpic-power-plus')->update(['is_active' => false]);
        Product::query()->where('slug', 'vim-lemon-gel')->update(['name' => 'Vim Citron Édition DB']);

        $this->get('/shop')->assertOk()->assertSee('Vim Citron Édition DB')->assertDontSee('Harpic Power Plus');
        $this->get('/product/harpic-power-plus')->assertNotFound();
        $this->get('/product/vim-lemon-gel')->assertOk();
    }

    public function test_seeder_maps_the_bundled_catalogue_with_relations(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(count(Catalog::seedProducts()), Product::query()->count());
        $product = Product::query()->where('slug', 'dettol-original')->firstOrFail();
        $this->assertSame('hygiene', $product->category->slug);
        $this->assertSame('Dettol', $product->brand->name);
        $this->assertGreaterThan(0, $product->stock);
        $this->assertSame(1, $product->stockMovements()->count());
    }
}
