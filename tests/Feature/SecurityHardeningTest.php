<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_carries_the_hardening_headers(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);
        $this->assertStringNotContainsString('*', str_replace('https:', '', $csp));
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertFalse($this->get('http://makerade.test/')->headers->has('Strict-Transport-Security'), 'HSTS is only sent over HTTPS');
    }

    public function test_hsts_is_sent_over_https_and_csp_can_be_switched_off(): void
    {
        $this->get('https://makerade.test/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        config(['shop.security.csp' => false]);
        $this->get('/')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_private_areas_are_never_cached(): void
    {
        $this->get('/account')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin/login')->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringNotContainsString('no-store', (string) $this->get('/shop')->headers->get('Cache-Control'));
    }

    public function test_cdn_assets_are_pinned_with_subresource_integrity(): void
    {
        $this->get('/')->assertSee('integrity="sha384-', false)->assertSee('crossorigin="anonymous"', false);
        $this->get('/admin/login')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSee('integrity="sha384-', false);
    }

    public function test_admin_login_is_locked_after_repeated_failures_even_from_other_addresses(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'a-strong-password']);

        foreach (range(1, 8) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'wrong'.$i]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.9.9'])->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'a-strong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_default_admin_password_is_refused_in_production(): void
    {
        config(['shop.admin.password' => 'password', 'shop.admin.email' => 'boss@example.com']);
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        (new AdminUserSeeder)->run();
    }

    public function test_a_strong_admin_password_is_accepted_in_production(): void
    {
        config(['shop.admin.password' => 'a-very-long-passphrase', 'shop.admin.email' => 'boss@example.com']);
        $this->app['env'] = 'production';

        (new AdminUserSeeder)->run();

        $this->assertTrue(User::query()->where('email', 'boss@example.com')->firstOrFail()->isAdmin());
    }

    public function test_csv_exports_neutralise_spreadsheet_formulas(): void
    {
        NewsletterSubscriber::query()->create(['phone' => '+2250700000000', 'email' => null]);
        NewsletterSubscriber::query()->create(['phone' => null, 'email' => '=HYPERLINK("http://evil.test","x")@a.com']);

        $csv = $this->actingAs(User::factory()->admin()->create())->get(route('admin.subscribers.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('+2250700000000', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)=HYPERLINK/m', $csv);
    }

    public function test_uploaded_svg_images_are_rejected_in_the_product_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->post(route('admin.products.store'), ['image' => $svg])->assertSessionHasErrors('image');
        $this->post(route('admin.brands.store'), ['name' => 'X', 'color' => '#111111', 'style' => 'plain', 'logo_file' => $svg])->assertSessionHasErrors('logo_file');
    }

    public function test_script_links_configured_in_the_admin_are_neutralised(): void
    {
        $this->assertSame('#', Setting::link('javascript:alert(1)'));
        $this->assertSame('#', Setting::link(' JaVa	ScRiPt:alert(1)'));
        $this->assertSame('#', Setting::link('data:text/html;base64,PHNjcmlwdD4='));
        $this->assertSame('https://example.com/x', Setting::link('https://example.com/x'));
        $this->assertSame(url('/shop'), Setting::link('/shop'));

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.popups.store'), ['name' => 'X', 'kind' => 'promo', 'button_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('button_url');
    }
}
