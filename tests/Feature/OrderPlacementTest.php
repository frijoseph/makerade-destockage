<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use App\StockMovementType;
use App\Support\OrderPlacement;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_placing_an_order_computes_totals_and_takes_stock(): void
    {
        config(['shop.shipping_fee' => 5, 'shop.free_shipping_threshold' => 100]);
        $soap = Product::factory()->create(['price' => 2.50, 'stock' => 10]);
        $powder = Product::factory()->create(['price' => 8, 'stock' => 4]);

        $order = OrderPlacement::place(
            ['name' => 'Koffi Yao', 'phone' => '07 01 02 03 04', 'city' => 'Bouaké', 'address' => 'Quartier Commerce'],
            [$soap->id => 3, $powder->id => 1],
        );

        $this->assertSame('MK-'.now()->format('Y').'-00001', $order->number);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertEquals(15.50, $order->subtotal);
        $this->assertEquals(5, $order->shipping_fee);
        $this->assertEquals(20.50, $order->total);
        $this->assertCount(2, $order->items);
        $this->assertSame(7, $soap->fresh()->stock);
        $this->assertSame(3, $powder->fresh()->stock);
        $this->assertSame(2, $order->stockMovements()->where('type', StockMovementType::Sale)->count());
        $this->assertSame('0701020304', $order->customer->phone);
    }

    public function test_free_shipping_applies_above_the_threshold(): void
    {
        config(['shop.shipping_fee' => 5, 'shop.free_shipping_threshold' => 20]);
        $product = Product::factory()->create(['price' => 10]);

        $order = OrderPlacement::place(['name' => 'Awa', 'phone' => '0700000000'], [$product->id => 2]);

        $this->assertEquals(0, $order->shipping_fee);
        $this->assertEquals(20, $order->total);
    }

    public function test_returning_customers_are_matched_by_phone(): void
    {
        $product = Product::factory()->create();

        OrderPlacement::place(['name' => 'Fatou', 'phone' => '+225 07 11 22 33 44'], [$product->id => 1]);
        OrderPlacement::place(['name' => 'Fatou Diallo', 'phone' => '+2250711223344', 'city' => 'Daloa'], [$product->id => 1]);

        $this->assertSame(1, Customer::query()->count());
        $customer = Customer::query()->first();
        $this->assertSame('Fatou Diallo', $customer->name);
        $this->assertSame('Daloa', $customer->city);
        $this->assertSame(2, $customer->orders()->count());
    }

    public function test_order_is_refused_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        try {
            OrderPlacement::place(['name' => 'Yao', 'phone' => '0700000001'], [$product->id => 2]);
            $this->fail('An exception was expected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stock insuffisant', $exception->getMessage());
        }

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_inactive_products_cannot_be_ordered(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->expectException(DomainException::class);

        OrderPlacement::place(['name' => 'Yao', 'phone' => '0700000001'], [$product->id => 1]);
    }

    public function test_cancelling_puts_stock_back_and_reactivating_takes_it_again(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $admin = User::factory()->admin()->create();
        $order = OrderPlacement::place(['name' => 'Aya', 'phone' => '0700000002'], [$product->id => 2]);

        OrderPlacement::changeStatus($order, OrderStatus::Cancelled, $admin);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(1, $order->stockMovements()->where('type', StockMovementType::Return)->count());

        OrderPlacement::changeStatus($order, OrderStatus::Confirmed, $admin);
        $this->assertSame(3, $product->fresh()->stock);

        OrderPlacement::changeStatus($order, OrderStatus::Delivered, $admin);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_admin_can_record_a_whatsapp_order_and_update_it(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['price' => 4, 'stock' => 10]);

        $response = $this->actingAs($admin)->post(route('admin.orders.store'), [
            'customer' => ['name' => 'Moussa Traoré', 'phone' => '+2250505050505', 'city' => 'Korhogo'],
            'channel' => OrderChannel::WhatsApp->value,
            'payment_method' => PaymentMethod::OrangeMoney->value,
            'discount' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => '', 'quantity' => 1],
            ],
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame(OrderChannel::WhatsApp, $order->channel);
        $this->assertEquals(11, $order->total - $order->shipping_fee);
        $this->assertSame(7, $product->fresh()->stock);

        $this->patch(route('admin.orders.payment', $order), [
            'payment_status' => PaymentStatus::Paid->value,
            'payment_method' => PaymentMethod::Wave->value,
        ])->assertSessionHasNoErrors();

        $this->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(PaymentMethod::Wave, $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_admin_order_form_reports_missing_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), [
                'customer' => ['name' => 'Aya', 'phone' => '0700000003'],
                'channel' => 'phone',
                'payment_method' => 'cash_on_delivery',
                'items' => [['product_id' => $product->id, 'quantity' => 5]],
            ])
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::query()->count());
    }
}
