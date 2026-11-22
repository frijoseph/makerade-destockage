<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\OrderChannel;
use App\PaymentMethod;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_global_search_finds_orders_products_customers_coupons_and_pages(): void
    {
        $product = Product::factory()->create(['name' => 'Lessive Marseille', 'sku' => 'MK-LESS1']);
        $order = OrderPlacement::place(['name' => 'Marseille Koné', 'phone' => '0700000011'], [$product->id => 1]);
        Coupon::factory()->create(['code' => 'MARSEILLE10']);
        Page::factory()->create(['title' => 'Livraison Marseille']);

        $results = $this->getJson(route('admin.search', ['q' => 'marseille']))->assertOk()->json('results');
        $hints = collect($results)->pluck('hint')->implode(' | ');

        $this->assertStringContainsString('Commande', $hints);
        $this->assertStringContainsString('Produit', $hints);
        $this->assertStringContainsString('Client', $hints);
        $this->assertStringContainsString('Code promo', $hints);
        $this->assertContains(route('admin.orders.show', $order), collect($results)->pluck('url')->all());
    }

    public function test_global_search_ignores_short_queries_and_requires_a_staff_account(): void
    {
        $this->getJson(route('admin.search', ['q' => 'a']))->assertOk()->assertJsonPath('results', []);

        auth()->logout();
        $this->actingAs(User::factory()->create())->getJson(route('admin.search', ['q' => 'abc']))->assertUnauthorized();
    }

    public function test_orders_can_be_filtered_by_method_channel_and_period(): void
    {
        $product = Product::factory()->create(['stock' => 50]);
        $wave = OrderPlacement::place(['name' => 'Aya Wave', 'phone' => '0700000021'], [$product->id => 1], OrderChannel::Web, PaymentMethod::Wave);
        $cash = OrderPlacement::place(['name' => 'Bob Cash', 'phone' => '0700000022'], [$product->id => 1], OrderChannel::WhatsApp);
        $old = OrderPlacement::place(['name' => 'Vieux Client', 'phone' => '0700000023'], [$product->id => 1], OrderChannel::Web, PaymentMethod::Wave);
        $old->forceFill(['placed_at' => now()->subDays(60)])->save();

        $this->get(route('admin.orders.index', ['method' => 'wave']))->assertOk()->assertSee($wave->number)->assertSee($old->number)->assertDontSee($cash->number);
        $this->get(route('admin.orders.index', ['channel' => 'whatsapp']))->assertOk()->assertSee($cash->number)->assertDontSee($wave->number);
        $this->get(route('admin.orders.index', ['method' => 'wave', 'period' => '30d']))->assertOk()->assertSee($wave->number)->assertDontSee($old->number);
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('pay-ic', false);
    }

    public function test_products_can_be_filtered_by_brand_promotion_and_sorted(): void
    {
        $brand = Brand::factory()->create();
        $cheap = Product::factory()->create(['name' => 'Zeta cheap', 'brand_id' => $brand->id, 'price' => 1, 'old_price' => null]);
        $promo = Product::factory()->create(['name' => 'Alpha promo', 'brand_id' => null, 'price' => 9, 'old_price' => 12]);

        $this->get(route('admin.products.index', ['brand' => $brand->id]))->assertOk()->assertSee('Zeta cheap')->assertDontSee('Alpha promo');
        $this->get(route('admin.products.index', ['brand' => 'none']))->assertOk()->assertSee('Alpha promo')->assertDontSee('Zeta cheap');
        $this->get(route('admin.products.index', ['promo' => '1']))->assertOk()->assertSee('Alpha promo')->assertDontSee('Zeta cheap');
        $this->get(route('admin.products.index', ['sort' => 'price_desc']))->assertOk()->assertSeeInOrder(['Alpha promo', 'Zeta cheap']);
        $this->get(route('admin.products.index', ['sort' => 'price_asc']))->assertOk()->assertSeeInOrder(['Zeta cheap', 'Alpha promo']);
    }

    public function test_customers_can_be_segmented(): void
    {
        $product = Product::factory()->create(['stock' => 50]);
        OrderPlacement::place(['name' => 'Fidèle Aya', 'phone' => '0700000031'], [$product->id => 1]);
        OrderPlacement::place(['name' => 'Fidèle Aya', 'phone' => '0700000031'], [$product->id => 1]);
        OrderPlacement::place(['name' => 'Unique Bob', 'phone' => '0700000032'], [$product->id => 1]);
        Customer::query()->where('phone', '+2250700000032')->update(['user_id' => User::factory()->create()->id]);

        $this->get(route('admin.customers.index', ['segment' => 'repeat']))->assertOk()->assertSee('Fidèle Aya')->assertDontSee('Unique Bob');
        $this->get(route('admin.customers.index', ['segment' => 'account']))->assertOk()->assertSee('Unique Bob')->assertDontSee('Fidèle Aya');
        $this->get(route('admin.customers.index', ['segment' => 'guest']))->assertOk()->assertSee('Fidèle Aya')->assertDontSee('Unique Bob');
        $this->get(route('admin.customers.index', ['segment' => 'new']))->assertOk()->assertSee('Fidèle Aya');
    }

    public function test_smart_tables_and_searchable_selects_are_wired_on_admin_pages(): void
    {
        Coupon::factory()->count(2)->create();

        $this->get(route('admin.coupons.index'))->assertOk()->assertSee('data-smart', false)->assertSee('data-filter', false);
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('data-filters', false)->assertSee('tom-select', false);
        $this->get(route('admin.orders.show', Order::factory()->create()))->assertOk()->assertSee('data-icon', false);
    }

    public function test_checkout_shows_payment_methods_as_icon_cards_and_logos_can_be_overridden(): void
    {
        Setting::putMany(['payment_methods' => ['wave', 'orange_money']]);

        $this->get('/cart')->assertOk()->assertSee('pay-opt', false)->assertSee('Wave')->assertSee('Orange Money')->assertDontSee('MTN MoMo')->assertSee('<svg', false);

        file_put_contents(public_path('img/payments/wave.svg'), '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"></svg>');
        try {
            $this->get('/cart')->assertOk()->assertSee('img/payments/wave.svg', false);
        } finally {
            unlink(public_path('img/payments/wave.svg'));
        }
    }
}
