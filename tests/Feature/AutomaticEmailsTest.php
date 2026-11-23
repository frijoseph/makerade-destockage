<?php

namespace Tests\Feature;

use App\Mail\ShopMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\OrderStatus;
use App\Support\Mail\MailSettings;
use App\Support\Mail\Notifier;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomaticEmailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
        Setting::putMany(['shop_name' => 'Boutique Test', 'contact_email' => 'contact@boutique.test']);
        Mail::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(?string $email): array
    {
        return [
            'name' => 'Aya Kouassi', 'phone' => '+2250711223344', 'city' => 'Abidjan', 'address' => 'Cocody',
            'payment_method' => 'cash_on_delivery', 'items' => ['savon-test' => 2],
        ] + ($email ? ['email' => $email] : []);
    }

    public function test_a_web_order_alerts_the_shop_and_confirms_to_the_customer(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 10, 'price' => 2000]);

        $this->postJson(route('checkout.store'), $this->checkoutPayload('aya@client.test'))->assertCreated();

        Mail::assertSent(ShopMail::class, 2);
        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('contact@boutique.test') && str_starts_with($mail->mailSubject, 'Nouvelle commande'));
        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('aya@client.test') && str_contains($mail->mailSubject, Order::query()->firstOrFail()->number));
    }

    public function test_customers_without_an_email_only_trigger_the_shop_alert(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 10]);

        $this->postJson(route('checkout.store'), $this->checkoutPayload(null))->assertCreated();

        Mail::assertSent(ShopMail::class, 1);
        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('contact@boutique.test'));
    }

    public function test_each_email_can_be_switched_off(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 10]);
        Setting::putMany(['mail_admin_new_order' => false, 'mail_customer_order' => false]);

        $this->postJson(route('checkout.store'), $this->checkoutPayload('aya@client.test'))->assertCreated();
        Mail::assertNothingSent();

        Setting::putMany(['mail_admin_new_order' => true, 'mail_enabled' => false]);
        $this->postJson(route('checkout.store'), $this->checkoutPayload('aya@client.test'))->assertCreated();
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_never_breaks_the_order(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'stock' => 10]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP injoignable'));

        $this->postJson(route('checkout.store'), $this->checkoutPayload('aya@client.test'))->assertCreated();

        $this->assertSame(1, Order::query()->count());
    }

    public function test_status_changes_notify_the_customer_for_the_selected_statuses_only(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = OrderPlacement::place(['name' => 'Aya', 'phone' => '+2250711223344', 'email' => 'aya@client.test', 'city' => 'Abidjan'], [$product->id => 1]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Shipped->value])->assertSessionHasNoErrors();
        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('aya@client.test') && str_contains($mail->mailSubject, 'expédiée'));

        Mail::fake();
        Setting::putMany(['mail_customer_status_statuses' => ['delivered']]);
        $this->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Cancelled->value]);
        Mail::assertNothingSent();
    }

    public function test_contact_messages_alert_the_shop_with_a_reply_address(): void
    {
        $this->postJson(route('contact.store'), ['name' => 'Moussa', 'phone' => '+2250755443322', 'email' => 'moussa@client.test', 'message' => 'Bonjour, avez-vous du savon ?'])->assertCreated();

        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('contact@boutique.test') && $mail->hasReplyTo('moussa@client.test') && str_contains($mail->mailSubject, 'Moussa'));
        $this->assertSame(1, ContactMessage::query()->count());
    }

    public function test_smtp_settings_override_the_env_configuration_and_the_password_is_encrypted(): void
    {
        Setting::putMany(['mail_mode' => 'smtp', 'mail_host' => 'smtp.exemple.test', 'mail_port' => 465, 'mail_encryption' => 'ssl', 'mail_username' => 'boutique', 'mail_password' => 'secret-smtp', 'mail_from_address' => 'ventes@boutique.test', 'mail_from_name' => '{shop_name}']);

        MailSettings::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.exemple.test', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('secret-smtp', config('mail.mailers.smtp.password'));
        $this->assertSame('ventes@boutique.test', config('mail.from.address'));
        $this->assertSame('Boutique Test', config('mail.from.name'));
        $this->assertStringNotContainsString('secret-smtp', (string) DB::table('settings')->where('key', 'mail_password')->value('value'));
    }

    public function test_env_mode_keeps_the_env_configuration(): void
    {
        config(['mail.default' => 'log']);
        Setting::putMany(['mail_mode' => 'env', 'mail_host' => 'smtp.exemple.test']);

        MailSettings::apply();

        $this->assertSame('log', config('mail.default'));
    }

    public function test_admin_can_send_a_test_email_and_gets_the_failure_reason(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.settings.edit', 'emails'))->assertOk()->assertSee('Tester l’envoi')->assertSee('Serveur SMTP');

        $this->post(route('admin.settings.mail-test'), ['to' => 'moi@exemple.test'])->assertSessionHas('status');
        Mail::assertSent(ShopMail::class, fn (ShopMail $mail): bool => $mail->hasTo('moi@exemple.test'));

        $this->post(route('admin.settings.mail-test'), ['to' => 'pas-un-mail'])->assertSessionHasErrors('to');
    }

    public function test_the_email_layout_renders_the_order_recap(): void
    {
        $product = Product::factory()->create(['name' => 'Savon Aloe', 'stock' => 10, 'price' => 2000]);
        $order = OrderPlacement::place(['name' => 'Aya Kouassi', 'phone' => '+2250711223344', 'email' => 'aya@client.test', 'city' => 'Abidjan'], [$product->id => 2]);

        Notifier::orderPlaced($order);

        Mail::assertSent(ShopMail::class, function (ShopMail $mail) use ($order): bool {
            if (! $mail->hasTo('aya@client.test')) {
                return false;
            }

            $html = $mail->render();

            return str_contains($html, 'Savon Aloe') && str_contains($html, $order->number) && str_contains($html, 'Voir ma commande') && str_contains($html, 'Boutique Test');
        });
    }

    public function test_email_settings_are_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.settings.mail-test'), ['to' => 'x@y.test'])->assertRedirect();
        Mail::assertNothingSent();
    }
}
