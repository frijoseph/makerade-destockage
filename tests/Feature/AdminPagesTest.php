<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'dashboard 7 days' => ['/admin?period=7d'],
            'dashboard this month' => ['/admin?period=month'],
            'orders' => ['/admin/orders'],
            'orders filtered' => ['/admin/orders?status=pending&q=MK'],
            'order create' => ['/admin/orders/create'],
            'order show' => ['/admin/orders/{order}'],
            'products' => ['/admin/products?stock=low'],
            'product create' => ['/admin/products/create'],
            'product edit' => ['/admin/products/{product}/edit'],
            'categories' => ['/admin/categories'],
            'category edit' => ['/admin/categories/{category}/edit'],
            'brands' => ['/admin/brands'],
            'customers' => ['/admin/customers?sort=spent'],
            'customer show' => ['/admin/customers/{customer}'],
            'stock' => ['/admin/stock'],
            'settings' => ['/admin/settings'],
        ];
    }

    #[DataProvider('pages')]
    public function test_admin_pages_render(string $uri): void
    {
        $product = Product::factory()->discounted()->create();
        Product::factory()->lowStock()->create();
        $order = OrderPlacement::place(['name' => 'Awa Koné', 'phone' => '+2250701020304', 'city' => 'Abidjan'], [$product->id => 2]);

        $uri = strtr($uri, [
            '{order}' => (string) $order->id,
            '{product}' => (string) $product->id,
            '{category}' => (string) $product->category_id,
            '{customer}' => (string) Customer::query()->value('id'),
        ]);

        $this->actingAs(User::factory()->admin()->create())->get($uri)->assertOk();
    }

    public function test_settings_are_saved_and_used_for_prices(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), [
                'shop_name' => 'Makerade',
                'currency_symbol' => 'FCFA',
                'currency_position' => 'after',
                'currency_decimals' => 0,
                'shipping_fee' => 1000,
                'free_shipping_threshold' => 25000,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('FCFA', Setting::get('currency_symbol'));
        $this->assertSame(1000.0, OrderPlacement::shippingFeeFor(5000));
        $this->assertSame(0.0, OrderPlacement::shippingFeeFor(30000));
    }
}
