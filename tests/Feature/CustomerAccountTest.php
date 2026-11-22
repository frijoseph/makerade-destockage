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

    public function test_registration_creates_the_account_and_offers_the_welcome_code(): void
    {
        Coupon::factory()->create(['code' => 'BIENVENUE10']);
        Setting::putMany(['welcome_coupon_code' => 'BIENVENUE10']);

        $this->post(route('account.register'), $this->registration())
            ->assertRedirect(route('account'))
            ->assertSessionHas('welcome_code.code', 'BIENVENUE10');

        $user = User::query()->where('email', 'aya@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(1, Customer::query()->where('user_id', $user->id)->count(), 'a brand-new phone number is linked right away');

        $this->get('/account')->assertOk()->assertSee('Bonjour, Aya');
    }

    public function test_past_orders_are_not_attached_by_a_bare_phone_number_but_can_be_claimed_with_proof(): void
    {
        $product = Product::factory()->create(['price' => 2500, 'stock' => 10]);
        $order = OrderPlacement::place(['name' => 'Aya', 'phone' => '+2250711223344'], [$product->id => 1]);

        $this->post(route('account.register'), $this->registration())->assertRedirect(route('account'));
        $user = User::query()->where('email', 'aya@example.com')->firstOrFail();

        // anyone can type a phone number: the history stays hidden until the order number AND the amount are given
        $this->assertNull($order->customer->fresh()->user_id);
        $this->get('/account')->assertOk()->assertDontSee('<b>'.$order->number.'</b>', false);

        $this->post(route('account.claim'), ['number' => $order->number, 'total' => '999'])->assertSessionHasErrors('number', errorBag: 'claim');
        $this->assertNull($order->fresh()->user_id);

        $this->post(route('account.claim'), ['number' => strtolower($order->number), 'total' => number_format((float) $order->total, 2, ',', ' ')])->assertSessionHasNoErrors();
        $this->assertSame($user->id, $order->fresh()->user_id);
        $this->assertSame($user->id, $order->customer->fresh()->user_id);
        $this->get('/account')->assertOk()->assertSee('<b>'.$order->number.'</b>', false);
    }

    public function test_claiming_is_limited_and_only_works_for_orders_placed_with_the_account_phone(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'stock' => 50]);
        $theirs = OrderPlacement::place(['name' => 'Bob', 'phone' => '+2250799999999'], [$product->id => 1]);
        $user = User::factory()->create(['phone' => '+2250711223344']);
        $this->actingAs($user);

        $this->post(route('account.claim'), ['number' => $theirs->number, 'total' => (string) $theirs->total])->assertSessionHasErrors('number', errorBag: 'claim');
        $this->assertNull($theirs->fresh()->user_id);

        foreach (range(1, 5) as $i) {
            $this->post(route('account.claim'), ['number' => 'MK-2099-0000'.$i, 'total' => '1']);
        }
        $this->post(route('account.claim'), ['number' => $theirs->number, 'total' => (string) $theirs->total])->assertSessionHasErrors('number', errorBag: 'claim');
        $this->assertStringContainsString('Trop de tentatives', session('errors')->getBag('claim')->first('number'));
    }

    public function test_changing_the_profile_phone_never_hands_over_someone_elses_history(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $victimOrder = OrderPlacement::place(['name' => 'Victime', 'phone' => '+2250755555555', 'address' => 'Rue secrète'], [$product->id => 1]);
        $attacker = User::factory()->create(['phone' => '+2250700000001']);

        $this->actingAs($attacker)->put(route('account.profile'), ['name' => 'Pirate', 'phone' => '0755555555', 'email' => $attacker->email])->assertSessionHasNoErrors();

        $this->assertNull($victimOrder->customer->fresh()->user_id);
        $this->assertSame('Victime', $victimOrder->customer->fresh()->name);
        $this->get('/account')->assertOk()->assertDontSee('<b>'.$victimOrder->number.'</b>', false)->assertDontSee('Rue secrète');
    }

    public function test_ordering_for_another_phone_while_logged_in_does_not_link_that_customer(): void
    {
        $product = Product::factory()->create(['slug' => 'savon-x', 'price' => 10, 'stock' => 10]);
        $victimOrder = OrderPlacement::place(['name' => 'Victime', 'phone' => '+2250755555555'], [$product->id => 1]);
        $attacker = User::factory()->create(['phone' => '+2250700000001']);

        $this->actingAs($attacker)->postJson(route('checkout.store'), [
            'name' => 'Pirate', 'phone' => '0755555555', 'city' => 'Abidjan', 'address' => 'Ailleurs', 'payment_method' => 'wave', 'items' => ['savon-x' => 1],
        ])->assertCreated();

        $this->assertNull($victimOrder->customer->fresh()->user_id, 'the victim customer is not attached to the attacker');
        $page = $this->get('/account')->assertOk();
        $this->assertStringNotContainsString('<b>'.$victimOrder->number.'</b>', $page->getContent(), 'the victim order is not listed');
        $this->assertSame(1, $attacker->orders()->count(), 'only the order the attacker placed is in the account');
    }

    public function test_repeated_wrong_passwords_lock_the_account_for_a_while(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        foreach (range(1, 8) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.1.0.{$i}"])->post(route('account.login'), ['login' => $user->email, 'password' => 'faux'.$i]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.9.9'])->post(route('account.login'), ['login' => $user->email, 'password' => 'secret-pass'])->assertSessionHasErrorsIn('login', 'login');
        $this->assertGuest();
        $this->assertStringContainsString('Trop de tentatives', session('errors')->getBag('login')->first('login'));
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

        $this->from('/account')->post(route('account.login'), ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrorsIn('login', 'login');
        $this->assertGuest();

        $this->post(route('account.login'), ['email' => $user->email, 'password' => 'secret-pass'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('account.logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass', 'is_active' => false]);

        $this->post(route('account.login'), ['email' => $user->email, 'password' => 'secret-pass'])->assertSessionHasErrorsIn('login', 'login');
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

        $this->assertSame($user->id, Customer::query()->where('phone', '+2250700000077')->value('user_id'));
        $this->get('/account')->assertOk()->assertSee('MK-'.now()->format('Y'));
    }
}
