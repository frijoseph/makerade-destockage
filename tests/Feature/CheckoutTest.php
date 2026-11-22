<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\OrderChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $items): array
    {
        return [
            'name' => 'Adjoua Kouassi',
            'phone' => '+225 07 08 09 10 11',
            'city' => 'Abidjan',
            'address' => 'Yopougon, près du marché',
            'payment_method' => 'wave',
            'items' => $items,
        ];
    }

    public function test_storefront_cart_becomes_an_order_with_server_prices(): void
    {
        Setting::putMany(['shipping_fee' => 3, 'free_shipping_threshold' => 1000]);
        $product = Product::factory()->create(['slug' => 'savon-test', 'price' => 2, 'stock' => 9]);

        $response = $this->postJson(route('checkout.store'), $this->payload(['savon-test' => 4]) + ['price' => 0.01]);

        $response->assertCreated()->assertJsonStructure(['number', 'total', 'redirect']);
        $order = Order::query()->firstOrFail();
        $this->assertSame(OrderChannel::Web, $order->channel);
        $this->assertEquals(11, $order->total);
        $this->assertSame(5, $product->fresh()->stock);

        $this->get($response->json('redirect'))->assertOk()->assertSee($order->number);
    }

    public function test_guests_can_order_without_an_account_and_are_invited_to_create_one(): void
    {
        Setting::putMany(['confirm_account_cta' => 'Créez votre compte gratuit', 'checkout_guest_note' => 'Commande sans compte possible']);
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 5]);

        $this->assertGuest();
        $this->get(route('cart'))->assertOk()->assertSee('Commande sans compte possible');

        $redirect = $this->postJson(route('checkout.store'), $this->payload(['savon-test' => 1]))->assertCreated()->json('redirect');

        $this->get($redirect)->assertOk()->assertSee('Créez votre compte gratuit')->assertSee('Suivre ma commande');
    }

    public function test_order_confirmation_requires_a_signed_link(): void
    {
        Product::factory()->create(['slug' => 'savon-test']);
        $number = $this->postJson(route('checkout.store'), $this->payload(['savon-test' => 1]))->json('number');

        $this->get(route('checkout.show', $number))->assertForbidden();
    }

    public function test_unknown_or_hidden_products_are_rejected(): void
    {
        Product::factory()->inactive()->create(['slug' => 'masque']);

        $this->postJson(route('checkout.store'), $this->payload(['masque' => 1]))->assertUnprocessable();
        $this->postJson(route('checkout.store'), $this->payload(['inconnu' => 1]))->assertUnprocessable();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_out_of_stock_cart_is_rejected_with_a_message(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 1]);

        $this->postJson(route('checkout.store'), $this->payload(['savon-test' => 3]))
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Stock insuffisant pour « '.Product::query()->value('name').' » (1 disponible(s)).']);
    }

    public function test_checkout_validates_customer_details(): void
    {
        $this->postJson(route('checkout.store'), ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'city', 'address', 'payment_method', 'items']);
    }
}
