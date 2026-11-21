<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\StockMovementType;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Javel Express',
            'variant' => 'Eau de Javel 1 L',
            'sku' => 'MK-09999',
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'price' => 1.5,
            'old_price' => 2,
            'cost_price' => 0.9,
            'stock' => 40,
            'low_stock_threshold' => 8,
            'kind' => 'bottle',
            'c1' => '#1f7a34',
            'c2' => '#ffffff',
            'is_active' => '1',
            'is_new' => '1',
        ], $overrides);
    }

    public function test_creating_a_product_records_its_initial_stock(): void
    {
        Storage::fake('public');

        $this->post(route('admin.products.store'), $this->validData(['image' => UploadedFile::fake()->image('javel.jpg')]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::query()->firstOrFail();
        $this->assertSame('javel-express-eau-de-javel-1-l', $product->slug);
        $this->assertSame(40, $product->stock);
        $this->assertSame(25, $product->discount_percent);
        $this->assertTrue($product->is_new);
        $this->assertFalse($product->is_hot);
        Storage::disk('public')->assertExists($product->image);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => StockMovementType::Restock->value, 'quantity' => 40]);
    }

    public function test_crossed_out_price_must_be_higher_than_the_price(): void
    {
        $this->post(route('admin.products.store'), $this->validData(['old_price' => 1]))
            ->assertSessionHasErrors('old_price');
    }

    public function test_stock_cannot_be_changed_from_the_product_form(): void
    {
        $product = Product::factory()->create(['stock' => 12]);

        $this->put(route('admin.products.update', $product), $this->validData(['sku' => $product->sku, 'stock' => 999]))
            ->assertSessionHasErrors('stock');

        $this->assertSame(12, $product->fresh()->stock);
    }

    public function test_updating_a_product(): void
    {
        $product = Product::factory()->create();
        $data = $this->validData(['sku' => $product->sku, 'name' => 'Nouveau nom']);
        unset($data['stock']);

        $this->put(route('admin.products.update', $product), $data)->assertSessionHasNoErrors();

        $this->assertSame('Nouveau nom', $product->fresh()->name);
    }

    public function test_ordered_products_are_hidden_instead_of_deleted(): void
    {
        $ordered = Product::factory()->create();
        $unused = Product::factory()->create();
        OrderPlacement::place(['name' => 'Yao', 'phone' => '0700000009'], [$ordered->id => 1]);

        $this->delete(route('admin.products.destroy', $ordered));
        $this->delete(route('admin.products.destroy', $unused));

        $this->assertFalse($ordered->fresh()->is_active);
        $this->assertModelMissing($unused);
    }

    public function test_categories_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('admin.categories.destroy', $product->category))->assertSessionHasErrors('category');
        $this->assertModelExists($product->category);
    }

    public function test_categories_and_brands_can_be_created(): void
    {
        $this->post(route('admin.categories.store'), ['name' => 'Jardin', 'bg' => '#eeeeee', 'position' => 9, 'is_active' => '1'])
            ->assertRedirect(route('admin.categories.index'));
        $this->post(route('admin.brands.store'), ['name' => 'Omo', 'color' => '#1c4fa3', 'style' => 'pill', 'is_active' => '1'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('categories', ['slug' => 'jardin']);
        $this->assertDatabaseHas('brands', ['slug' => 'omo']);
    }
}
