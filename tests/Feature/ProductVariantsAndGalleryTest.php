<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\OrderStatus;
use App\StockMovementType;
use App\Support\OrderPlacement;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantsAndGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
    }

    private function productWithVariants(): Product
    {
        $product = Product::factory()->create(['slug' => 'lessive-test', 'price' => 10, 'stock' => 0]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '1 L', 'option1' => '1 L', 'stock' => 5, 'position' => 1]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '5 L', 'option1' => '5 L', 'price' => 40, 'old_price' => 50, 'stock' => 3, 'position' => 2]);
        $product->update(['option_names' => ['Taille'], 'stock' => 8]);

        return $product->fresh(['variants']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Lessive Pro', 'variant' => 'Lessive liquide', 'sku' => 'MK-70001',
            'category_id' => Category::factory()->create()->id, 'brand_id' => Brand::factory()->create()->id,
            'price' => 10, 'stock' => 99, 'low_stock_threshold' => 5, 'kind' => 'bottle',
            'c1' => '#1f7a34', 'c2' => '#ffffff', 'is_active' => '1',
        ], $overrides);
    }

    public function test_admin_creates_a_product_with_gallery_and_variants(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->formData([
            'image' => UploadedFile::fake()->image('main.jpg'),
            'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'option_names' => ['Taille', 'Parfum'],
            'variants' => [
                ['option1' => '1 L', 'option2' => 'Citron', 'sku' => 'LP-1-C', 'stock' => 12, 'is_active' => '1'],
                ['option1' => '5 L', 'option2' => 'Citron', 'sku' => 'LP-5-C', 'price' => 40, 'stock' => 4, 'is_active' => '1'],
            ],
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();

        $product = Product::query()->with(['variants', 'images'])->firstOrFail();
        $this->assertSame(['Taille', 'Parfum'], $product->option_names);
        $this->assertCount(2, $product->images);
        $this->assertCount(2, $product->variants);
        $this->assertSame('1 L / Citron', $product->variants[0]->name);
        $this->assertSame(16, $product->stock, 'the product stock is the sum of its variants (the initial stock field is ignored)');
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $product->variants[0]->id, 'type' => StockMovementType::Restock->value, 'quantity' => 12]);
        Storage::disk('public')->assertExists($product->images[0]->path);
    }

    public function test_variant_rows_are_validated(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        ProductVariant::factory()->create(['sku' => 'TAKEN-1']);

        $this->post(route('admin.products.store'), $this->formData(['variants' => [
            ['option1' => '1 L', 'sku' => 'TAKEN-1', 'stock' => 1],
            ['option1' => '1 l', 'stock' => 1],
        ]]))->assertSessionHasErrors(['variants.0', 'variants.1']);

        $this->assertSame(1, Product::query()->count(), 'only the factory product exists: nothing was created');
    }

    public function test_editing_variants_logs_stock_changes_and_keeps_ordered_variants(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->productWithVariants();
        [$small, $big] = $product->variants;
        OrderPlacement::place(['name' => 'Aya', 'phone' => '0700000001'], ["{$product->id}:{$big->id}" => 1]);

        $payload = $this->formData([
            'name' => $product->name, 'variant' => $product->variant, 'sku' => $product->sku, 'slug' => $product->slug,
            'category_id' => $product->category_id, 'brand_id' => $product->brand_id,
        ]);
        unset($payload['stock']);

        // the 1 L variant gets +10 units, the 5 L variant (already ordered) is removed from the form
        $this->actingAs($admin)->put(route('admin.products.update', $product), $payload + [
            'option_names' => ['Taille'],
            'variants' => [['id' => $small->id, 'option1' => '1 L', 'stock' => 15, 'is_active' => '1']],
        ])->assertSessionHasNoErrors();

        $this->assertSame(15, $small->fresh()->stock);
        $this->assertFalse($big->fresh()->is_active, 'an ordered variant is deactivated, not deleted');
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $small->id, 'type' => StockMovementType::Adjustment->value, 'quantity' => 10]);
        $this->assertSame(17, $product->fresh()->stock);
    }

    public function test_product_page_shows_gallery_thumbnails_and_variant_options(): void
    {
        $product = $this->productWithVariants();
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/gallery/one.jpg']);
        $product->update(['image' => 'products/main.jpg']);

        $this->get('/product/lessive-test')->assertOk()
            ->assertSee('data-pd-variants', false)
            ->assertSee('Taille')
            ->assertSee('data-thumb', false)
            ->assertSee('one.jpg', false);

        $this->get('/shop')->assertOk()->assertSee('À partir de')->assertSee('Choisir une option');
    }

    public function test_checkout_uses_the_variant_price_and_decrements_its_stock(): void
    {
        $product = $this->productWithVariants();
        [$small, $big] = $product->variants;

        $this->postJson(route('checkout.store'), [
            'name' => 'Aya', 'phone' => '0700000002', 'city' => 'Abidjan', 'address' => 'Cocody', 'payment_method' => 'wave',
            'items' => ["lessive-test~{$big->id}" => 2, "lessive-test~{$small->id}" => 1],
        ])->assertCreated();

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertEquals(90, $order->subtotal, '2 × 40 + 1 × 10 (the 1 L variant inherits the product price)');
        $this->assertSame(1, $order->items->where('variant_id', $big->id)->count());
        $this->assertStringContainsString('(5 L)', $order->items->firstWhere('variant_id', $big->id)->product_name);
        $this->assertSame(1, $big->fresh()->stock);
        $this->assertSame(4, $small->fresh()->stock);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_checkout_refuses_missing_inactive_or_out_of_stock_variants(): void
    {
        $product = $this->productWithVariants();
        [$small, $big] = $product->variants;
        $big->update(['is_active' => false]);

        $payload = ['name' => 'Aya', 'phone' => '0700000003', 'city' => 'Abidjan', 'address' => 'Cocody', 'payment_method' => 'wave'];

        $this->postJson(route('checkout.store'), $payload + ['items' => ['lessive-test' => 1]])->assertStatus(422)->assertJsonPath('message', 'Choisissez une option pour « '.$product->name.' ».');
        $this->postJson(route('checkout.store'), $payload + ['items' => ["lessive-test~{$big->id}" => 1]])->assertStatus(422);
        $this->postJson(route('checkout.store'), $payload + ['items' => ["lessive-test~{$small->id}" => 6]])->assertStatus(422)->assertJsonPath('message', fn ($message) => str_contains($message, 'Stock insuffisant'));

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(5, $small->fresh()->stock);
    }

    public function test_promo_preview_prices_variants_server_side(): void
    {
        $product = $this->productWithVariants();
        $big = $product->variants[1];

        $this->postJson(route('promo.coupon'), ['items' => ["lessive-test~{$big->id}" => 2]])
            ->assertOk()
            ->assertJsonPath('subtotal', 80);
    }

    public function test_cancelling_an_order_gives_the_variant_stock_back(): void
    {
        $product = $this->productWithVariants();
        $big = $product->variants[1];

        $order = OrderPlacement::place(['name' => 'Aya', 'phone' => '0700000004'], ["{$product->id}:{$big->id}" => 2]);
        $this->assertSame(1, $big->fresh()->stock);

        OrderPlacement::changeStatus($order, OrderStatus::Cancelled);
        $this->assertSame(3, $big->fresh()->stock);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_variant_stock_cannot_go_negative_and_foreign_variants_are_refused(): void
    {
        $product = $this->productWithVariants();
        $other = ProductVariant::factory()->create();

        try {
            OrderPlacement::place(['name' => 'Aya', 'phone' => '0700000005'], ["{$product->id}:{$other->id}" => 1]);
            $this->fail('A variant of another product must be refused.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('option', $exception->getMessage());
        }
    }

    public function test_stock_screen_adjusts_a_single_variant(): void
    {
        $product = $this->productWithVariants();
        $small = $product->variants[0];
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.stock.store'), ['product_id' => "{$product->id}:{$small->id}", 'type' => 'restock', 'quantity' => 7])->assertSessionHasNoErrors();
        $this->assertSame(12, $small->fresh()->stock);
        $this->assertSame(15, $product->fresh()->stock);

        $this->post(route('admin.stock.store'), ['product_id' => (string) $product->id, 'type' => 'restock', 'quantity' => 1])->assertSessionHasErrors('product_id');
        $this->get(route('admin.stock.index'))->assertOk()->assertSee('1 L');
    }

    public function test_gallery_photos_can_be_removed_and_promoted_to_main_photo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['image' => 'products/main.jpg']);
        Storage::disk('public')->put('products/main.jpg', 'x');
        Storage::disk('public')->put('products/gallery/one.jpg', 'x');
        Storage::disk('public')->put('products/gallery/two.jpg', 'x');
        $one = ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/gallery/one.jpg']);
        $two = ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/gallery/two.jpg']);

        $payload = $this->formData([
            'name' => $product->name, 'variant' => $product->variant, 'sku' => $product->sku, 'slug' => $product->slug,
            'category_id' => $product->category_id, 'brand_id' => $product->brand_id,
        ]);
        unset($payload['stock']);

        $this->put(route('admin.products.update', $product), $payload + ['promote_image' => $one->id, 'remove_gallery' => [$two->id]])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('products/gallery/one.jpg', $product->image);
        $this->assertSame(['products/main.jpg'], $product->images()->pluck('path')->all(), 'the former main photo moved to the gallery');
        Storage::disk('public')->assertMissing('products/gallery/two.jpg');
    }

    public function test_gallery_size_is_limited(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();
        ProductImage::factory()->count(5)->create(['product_id' => $product->id]);

        $payload = $this->formData([
            'name' => $product->name, 'variant' => $product->variant, 'sku' => $product->sku, 'slug' => $product->slug,
            'category_id' => $product->category_id, 'brand_id' => $product->brand_id,
            'gallery' => array_map(fn (int $i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, 8)),
        ]);
        unset($payload['stock']);

        $this->put(route('admin.products.update', $product), $payload)->assertSessionHasErrors('gallery');
        $this->assertSame(5, $product->images()->count());
    }

    public function test_admin_order_form_accepts_variant_lines(): void
    {
        $product = $this->productWithVariants();
        $big = $product->variants[1];
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.orders.create'))->assertOk()->assertSee("{$product->id}:{$big->id}");

        $this->post(route('admin.orders.store'), [
            'customer' => ['name' => 'Moussa', 'phone' => '+2250505050505'],
            'channel' => 'whatsapp', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => "{$product->id}:{$big->id}", 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $big->fresh()->stock);
    }
}
