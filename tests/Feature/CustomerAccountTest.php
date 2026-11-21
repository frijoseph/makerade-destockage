<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderPlacement;
use App\UserRole;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Aya Koné', 'email' => 'Aya@Example.com', 'phone' => '+225 07 11 22 33 44',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass', 'terms' => '1',
        ], $overrides);
    }

    public function test_guests_see_the_login_and_register_forms(): void
    {
        $this->get('/account')->assertOk()->assertSee('Créer un compte')->assertSee('Mot de passe oublié');
        $this->get('/account/forgot-password')->assertOk();
    }

    public function test_registration_creates_the_account_links_old_orders_and_offers_the_welcome_code(): void
    {
        Coupon::factory()->create(['code' => 'BIENVENUE10']);
        Setting::putMany(['welcome_coupon_code' => 'BIENVENUE10']);
        $product = Product::factory()->create();
        OrderPlacement::place(['name' => 'Aya', 'phone' => '+2250711223344'], [$product->id => 1]);

        $this->post(route('account.register'), $this->registration())
            ->assertRedirect(route('account'))
            ->assertSessionHas('welcome_code.code', 'BIENVENUE10');

        $user = User::query()->where('email', 'aya@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(1, Customer::query()->where('user_id', $user->id)->count());

        $this->get('/account')->assertOk()->assertSee('Bonjour, Aya')->assertSee('MK-'.now()->format('Y'));
    }

    public function test_registration_is_validated(): void
    {
        User::factory()->create(['email' => 'aya@example.com']);

        $this->post(route('account.register'), $this->registration())->assertSessionHasErrors('email');
        $this->post(route('account.register'), $this->registration(['email' => 'new@example.com', 'password_confirmation' => 'autre']))->assertSessionHasErrors('password');
        $this->post(route('account.register'), $this->registration(['email' => 'new@example.com', 'terms' => null]))->assertSessionHasErrors('terms');
        $this->post(route('account.register'), $this->registration(['email' => 'new@example.com', 'password' => 'court', 'password_confirmation' => 'court']))->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_logout_and_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->from('/account')->post(route('account.login'), ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrorsIn('login', 'email');
        $this->assertGuest();

        $this->post(route('account.login'), ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('account.logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass', 'is_active' => false]);

        $this->post(route('account.login'), ['email' => $user->email, 'password' => 'secret-pass'])->assertSessionHasErrorsIn('login', 'email');
        $this->assertGuest();
    }

    public function test_customers_update_their_profile_and_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $this->actingAs($user);

        $this->put(route('account.profile'), ['name' => 'Nouveau Nom', 'email' => 'nouveau@example.com', 'phone' => '0700000042', 'city' => 'Daloa', 'address' => 'Centre'])->assertSessionHasNoErrors();
        $this->assertSame('Nouveau Nom', $user->fresh()->name);
        $this->assertDatabaseHas('customers', ['user_id' => $user->id, 'city' => 'Daloa']);

        $this->put(route('account.password'), ['current_password' => 'mauvais', 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])->assertSessionHasErrors('current_password');
        $this->put(route('account.password'), ['current_password' => 'secret-pass', 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])->assertSessionHasNoErrors();
        $this->post(route('account.logout'));
        $this->post(route('account.login'), ['email' => 'nouveau@example.com', 'password' => 'nouveau-pass'])->assertRedirect(route('account'));
    }

    public function test_password_reset_link_is_sent_and_works(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'ancien-pass']);

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'inconnu@example.com'])->assertSessionHas('status');
        Notification::assertSentToTimes($user, ResetPassword::class, 1);

        $token = Password::createToken($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee($token);

        $this->post(route('password.update'), ['token' => 'mauvais', 'email' => $user->email, 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])->assertSessionHasErrors('email');
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'nouveau-pass', 'password_confirmation' => 'nouveau-pass'])->assertRedirect(route('account'));

        $this->post(route('account.login'), ['email' => $user->email, 'password' => 'nouveau-pass'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_mail_points_to_the_storefront_reset_page(): void
    {
        $user = User::factory()->create();
        $mail = (new ResetPassword('tok123'))->toMail($user);

        $this->assertStringContainsString(route('password.reset', ['token' => 'tok123', 'email' => $user->email]), $mail->actionUrl);
        $this->assertStringContainsString(Setting::get('shop_name'), $mail->subject);
    }

    public function test_customers_cannot_use_the_admin_and_staff_keep_their_access(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/login')->assertOk();

        $this->actingAs(User::factory()->admin()->create())->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_guests_are_sent_to_the_right_login(): void
    {
        $this->post(route('account.logout'))->assertRedirect(route('account'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_checkout_links_the_order_to_the_logged_in_account(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'price' => 10, 'stock' => 5]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('checkout.store'), [
            'name' => 'Aya', 'phone' => '0700000077', 'city' => 'Abidjan', 'address' => 'Cocody', 'payment_method' => 'wave', 'items' => ['savon-test' => 1],
        ])->assertCreated();

        $this->assertSame($user->id, Customer::query()->where('phone', '0700000077')->value('user_id'));
        $this->get('/account')->assertOk()->assertSee('MK-'.now()->format('Y'));
    }
}
