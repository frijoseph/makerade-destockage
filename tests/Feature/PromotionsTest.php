<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::putMany(['shipping_fee' => 5, 'free_shipping_threshold' => 0]);
        Product::factory()->create(['slug' => 'savon-test', 'price' => 20, 'stock' => 50]);
    }

    /**
     * @return array<string, mixed>
     */
    private function order(array $extra = [], string $phone = '0700000001'): array
    {
        return [
            'name' => 'Aya Koné', 'phone' => $phone, 'city' => 'Abidjan', 'address' => 'Cocody',
            'payment_method' => 'wave', 'items' => ['savon-test' => 2],
        ] + $extra;
    }

    public function test_percent_code_reduces_the_total_and_is_counted(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'BIENVENUE10', 'value' => 10]);

        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'bienvenue10']))->assertCreated();

        $order = Order::query()->firstOrFail();
        $this->assertEquals(40, $order->subtotal);
        $this->assertEquals(4, $order->discount);
        $this->assertEquals(41, $order->total);
        $this->assertSame('BIENVENUE10', $order->coupon_code);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_unknown_expired_and_exhausted_codes_are_refused(): void
    {
        Coupon::factory()->expired()->create(['code' => 'VIEUX']);
        Coupon::factory()->create(['code' => 'EPUISE', 'usage_limit' => 1, 'used_count' => 1]);
        Coupon::factory()->create(['code' => 'GROS', 'min_subtotal' => 500]);

        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'NOPE']))->assertUnprocessable()->assertJsonFragment(['message' => 'Ce code promo n’existe pas.']);
        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'VIEUX']))->assertUnprocessable()->assertJsonFragment(['message' => 'Ce code promo a expiré.']);
        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'EPUISE']))->assertUnprocessable();
        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'GROS']))->assertUnprocessable();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_once_per_customer_is_checked_by_phone(): void
    {
        Coupon::factory()->create(['code' => 'UNFOIS', 'once_per_customer' => true]);

        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'UNFOIS']))->assertCreated();
        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'UNFOIS']))->assertUnprocessable();
        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'UNFOIS'], '0700000099'))->assertCreated();
    }

    public function test_free_shipping_code_and_percent_cap(): void
    {
        Coupon::factory()->freeShipping()->create(['code' => 'LIVRE']);
        Coupon::factory()->create(['code' => 'PLAFOND', 'value' => 50, 'max_discount' => 6]);

        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'LIVRE']))->assertCreated();
        $this->assertEquals(0, Order::query()->latest('id')->first()->shipping_fee);

        $this->postJson(route('checkout.store'), $this->order(['coupon_code' => 'PLAFOND'], '0700000002'))->assertCreated();
        $this->assertEquals(6, Order::query()->latest('id')->first()->discount);
    }

    public function test_automatic_promotion_applies_without_a_code(): void
    {
        Coupon::factory()->fixed(5)->automatic()->create(['code' => 'AUTO5', 'min_subtotal' => 30]);

        $this->postJson(route('checkout.store'), $this->order())->assertCreated();

        $order = Order::query()->firstOrFail();
        $this->assertEquals(5, $order->discount);
        $this->assertSame('AUTO5', $order->coupon_code);
    }

    public function test_cart_preview_shows_discount_and_shipping_without_storing_anything(): void
    {
        Coupon::factory()->create(['code' => 'DIX', 'value' => 10]);

        $this->postJson(route('promo.coupon'), ['items' => ['savon-test' => 2], 'code' => 'dix'])
            ->assertOk()
            ->assertJsonPath('subtotal', 40)
            ->assertJsonPath('discount', 4)
            ->assertJsonPath('shipping', 5)
            ->assertJsonPath('total', 41)
            ->assertJsonPath('coupon.code', 'DIX');

        $this->postJson(route('promo.coupon'), ['items' => ['savon-test' => 1], 'code' => 'FAUX'])->assertUnprocessable();
        $this->assertSame(0, Coupon::query()->first()->used_count);
    }

    public function test_admin_manages_coupons_from_the_modal(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->postJson(route('admin.coupons.store'), ['code' => ' été 20 ', 'type' => 'percent', 'value' => 20])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->postJson(route('admin.coupons.store'), ['code' => 'ETE20', 'label' => 'Été', 'type' => 'percent', 'value' => 20, 'min_subtotal' => null, 'is_active' => true, 'ends_at' => now()->addWeek()->toDateTimeString()])->assertCreated();
        $coupon = Coupon::query()->where('code', 'ETE20')->firstOrFail();

        $this->postJson(route('admin.coupons.store'), ['code' => 'ETE20', 'type' => 'fixed', 'value' => 3])->assertJsonValidationErrors('code');
        $this->postJson(route('admin.coupons.store'), ['code' => 'TROP', 'type' => 'percent', 'value' => 150])->assertJsonValidationErrors('value');

        $this->putJson(route('admin.coupons.update', $coupon), ['code' => 'ETE20', 'type' => 'fixed', 'value' => 3, 'is_active' => false])->assertOk();
        $this->assertSame('3.00', $coupon->fresh()->value);

        $this->deleteJson(route('admin.coupons.destroy', $coupon))->assertOk();
        $this->assertModelMissing($coupon);
        $this->get(route('admin.coupons.index'))->assertOk();
    }
}
