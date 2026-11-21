<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Page;
use App\Models\Popup;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'settings home' => ['/admin/settings/home'],
            'settings navigation' => ['/admin/settings/navigation'],
            'settings money' => ['/admin/settings/money'],
            'settings delivery' => ['/admin/settings/delivery'],
            'pages' => ['/admin/pages'],
            'articles' => ['/admin/pages?type=article'],
            'page create' => ['/admin/pages/create?type=article'],
            'page edit' => ['/admin/pages/{page}/edit'],
            'coupons' => ['/admin/coupons'],
            'popups' => ['/admin/popups'],
            'popup create' => ['/admin/popups/create'],
            'popup edit' => ['/admin/popups/{popup}/edit'],
            'settings marketing' => ['/admin/settings/marketing'],
            'messages' => ['/admin/messages'],
            'subscribers' => ['/admin/subscribers'],
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
            '{page}' => (string) Page::factory()->create()->id,
            '{popup}' => (string) Popup::factory()->wheel()->create()->id,
        ]);

        $this->actingAs(User::factory()->admin()->create())->get($uri)->assertOk();
    }

    public function test_currency_and_shipping_settings_drive_prices(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update', 'money'), ['settings' => [
            'currency_symbol' => 'FCFA',
            'currency_code' => 'XOF',
            'currency_position' => 'after',
            'currency_decimals' => '0',
            'decimal_separator' => 'comma',
            'thousands_separator' => 'space',
            'payment_methods' => ['wave', 'orange_money'],
        ]])->assertSessionHasNoErrors();

        $this->put(route('admin.settings.update', 'delivery'), ['settings' => [
            'shipping_fee' => 1000,
            'free_shipping_threshold' => 25000,
            'return_days' => 14,
            'product_perks' => [['icon' => 'truck', 'text' => 'Livraison offerte dès {free_shipping_threshold}']],
        ]])->assertSessionHasNoErrors();

        $this->assertSame("12\u{202F}500\u{00A0}FCFA", Money::format(12500));
        $this->assertSame(1000, Setting::get('shipping_fee'));
        $this->assertSame(1000.0, OrderPlacement::shippingFeeFor(5000));
        $this->assertSame(0.0, OrderPlacement::shippingFeeFor(30000));
        $this->assertSame(['wave', 'orange_money'], Setting::get('payment_methods'));
        $this->assertSame("Livraison offerte dès 25\u{202F}000\u{00A0}FCFA", Setting::interpolate(Setting::get('product_perks')[0]['text']));
    }

    public function test_settings_are_validated_from_the_schema(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update', 'delivery'), ['settings' => ['shipping_fee' => 'abc', 'free_shipping_threshold' => 0, 'return_days' => 30]])
            ->assertSessionHasErrors('settings.shipping_fee');

        $this->put(route('admin.settings.update', 'unknown'), ['settings' => []])->assertNotFound();
    }

    public function test_images_can_be_uploaded_in_settings_lists(): void
    {
        Storage::fake('public');
        $slides = Setting::get('hero_slides');

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update', 'home'), [
                'settings' => ['hero_slides' => [array_merge($slides[0], ['title' => 'Nouveau titre'])], 'flash_count' => 4, 'featured_count' => 8],
                'files' => ['hero_slides' => [['image' => UploadedFile::fake()->image('slide.jpg')]]],
            ])
            ->assertSessionHasNoErrors();

        $saved = Setting::get('hero_slides');
        $this->assertCount(1, $saved);
        $this->assertSame('Nouveau titre', $saved[0]['title']);
        Storage::disk('public')->assertExists($saved[0]['image']);
        $this->assertSame(4, Setting::get('flash_count'));
    }

    public function test_pages_and_articles_can_be_managed(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.pages.store'), ['type' => 'article', 'title' => 'Bien laver son linge', 'tag' => 'Lessive', 'body' => "## Astuce\n\nRetours sous {return_days} jours.", 'is_published' => '1'])
            ->assertRedirect(route('admin.pages.index', ['type' => 'article']));

        $this->get('/blog/bien-laver-son-linge')->assertOk()->assertSee('Astuce')->assertSee('Retours sous 30 jours.');
        $this->get('/blogs')->assertOk()->assertSee('Bien laver son linge');
    }
}
