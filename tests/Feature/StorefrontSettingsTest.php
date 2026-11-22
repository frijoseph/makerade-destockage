<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Setting;
use App\OrderStatus;
use App\Support\Catalog;
use App\Support\OrderPlacement;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
        $this->seed([CatalogSeeder::class, PageSeeder::class]);
    }

    public function test_shop_identity_and_contacts_come_from_the_settings(): void
    {
        Setting::putMany([
            'shop_name' => 'Boutique Test',
            'shop_tagline' => 'Slogan configurable',
            'delivery_zone' => 'Sénégal',
            'contact_phone' => '+221 77 000 00 00',
            'contact_email' => 'bonjour@boutique.test',
            'social_facebook' => 'https://facebook.com/boutique-test',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('<title>Boutique Test</title>', false)
            ->assertSee('Slogan configurable')
            ->assertSee('Sénégal')
            ->assertSee('https://facebook.com/boutique-test')
            ->assertDontSee('Makerade Destockage');

        $this->get('/contact')->assertOk()->assertSee('+221 77 000 00 00')->assertSee('bonjour@boutique.test');
        $this->get('/page/returns')->assertOk()->assertSee('30 jours')->assertSee('bonjour@boutique.test');
    }

    public function test_prices_use_the_configured_currency(): void
    {
        Setting::putMany(['currency_symbol' => 'FCFA', 'currency_position' => 'after', 'currency_decimals' => 0, 'thousands_separator' => 'space']);
        Product::query()->where('slug', 'harpic-power-plus')->update(['price' => 2500, 'old_price' => 3000]);

        $this->get('/product/harpic-power-plus')->assertOk()
            ->assertSee("2\u{202F}500\u{00A0}FCFA", false)
            ->assertDontSee('$2');
    }

    public function test_free_shipping_promise_is_hidden_when_disabled(): void
    {
        Setting::putMany(['free_shipping_threshold' => 0]);

        $this->get('/product/harpic-power-plus')->assertOk()->assertDontSee('Livraison gratuite dès');
    }

    public function test_homepage_shows_real_stats_and_no_countdown_without_end_date(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('data-countto="'.Product::query()->active()->count().'"', false);
        $response->assertDontSee('data-countdown', false);

        Setting::putMany(['flash_ends_at' => now()->addDay()->toDateTimeString()]);
        $this->get('/')->assertSee('data-countdown', false);
    }

    public function test_recent_sales_never_expose_customer_names(): void
    {
        $product = Product::query()->firstOrFail();
        OrderPlacement::place(['name' => 'Nom Très Privé', 'phone' => '0700000001', 'city' => 'Daloa'], [$product->id => 1]);

        $sales = Catalog::recentSales();
        $this->assertSame('Daloa', $sales[0]['city']);
        $this->assertSame($product->slug, $sales[0]['slug']);
        $this->get('/')->assertDontSee('Nom Très Privé');

        Setting::putMany(['recent_sales_enabled' => false]);
        $this->assertSame([], Catalog::recentSales());
    }

    public function test_contact_and_newsletter_forms_are_stored(): void
    {
        $this->postJson(route('contact.store'), ['name' => 'Aya', 'phone' => '07 11 22 33 44', 'message' => 'Bonjour'])->assertCreated();
        $this->postJson(route('newsletter.store'), ['email' => 'Aya@Example.com'])->assertCreated();
        $this->postJson(route('newsletter.store'), ['email' => 'aya@example.com'])->assertCreated();

        $this->assertSame(1, ContactMessage::query()->count());
        $this->assertSame(1, NewsletterSubscriber::query()->count());
        $this->postJson(route('contact.store'), ['email' => 'pas-un-email'])->assertJsonValidationErrors(['name', 'phone', 'message']);
    }

    public function test_customers_can_track_their_order_with_number_and_phone(): void
    {
        $order = OrderPlacement::place(['name' => 'Koffi', 'phone' => '+225 07 11 22 33 44'], [Product::query()->value('id') => 1]);
        OrderPlacement::changeStatus($order, OrderStatus::Shipped);

        $this->postJson(route('track.lookup'), ['number' => strtolower($order->number), 'phone' => '+2250711223344'])
            ->assertOk()
            ->assertJsonPath('status', 'Expédiée')
            ->assertJsonPath('steps.3.state', 'cur')
            ->assertJsonPath('steps.4.state', 'todo');

        $this->postJson(route('track.lookup'), ['number' => $order->number, 'phone' => '0799999999'])->assertNotFound();
    }

    public function test_checkout_only_accepts_enabled_payment_methods(): void
    {
        Setting::putMany(['payment_methods' => ['wave']]);

        $payload = ['name' => 'Aya', 'phone' => '0700000002', 'city' => 'Abidjan', 'address' => 'Cocody', 'items' => ['harpic-power-plus' => 1]];

        $this->postJson(route('checkout.store'), $payload + ['payment_method' => 'orange_money'])->assertJsonValidationErrors('payment_method');
        $this->postJson(route('checkout.store'), $payload + ['payment_method' => 'wave'])->assertCreated();
    }

    public function test_header_menu_and_footer_columns_are_configurable(): void
    {
        Setting::putMany([
            'header_menu' => [['label' => 'Promos du mois', 'url' => '/best-deals']],
            'footer_columns' => [['title' => 'Aide', 'links' => "FAQ | /page/terms\ncatégories"]],
        ]);

        $this->get('/shop')->assertOk()
            ->assertSee('Promos du mois')
            ->assertSee('<h4>Aide</h4>', false)
            ->assertSee('FAQ')
            ->assertSee('Entretien ménager');
    }
}
