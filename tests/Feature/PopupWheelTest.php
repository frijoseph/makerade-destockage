<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Popup;
use App\Models\User;
use App\Models\WheelSpin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupWheelTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_only_receives_live_popups_for_the_page_and_never_wheel_or_newsletter_codes(): void
    {
        Popup::factory()->create(['name' => 'Accueil', 'title' => 'Promo accueil', 'pages' => ['home'], 'coupon_code' => 'PUBLIC10']);
        Popup::factory()->create(['title' => 'Promo panier', 'pages' => ['cart']]);
        Popup::factory()->create(['title' => 'Désactivé', 'pages' => ['all'], 'is_active' => false]);
        Popup::factory()->create(['title' => 'Expiré', 'pages' => ['all'], 'ends_at' => now()->subDay()]);
        Popup::factory()->create(['title' => 'Bientôt', 'pages' => ['all'], 'starts_at' => now()->addDay()]);
        Popup::factory()->create(['title' => 'Newsletter', 'kind' => 'newsletter', 'pages' => ['home'], 'coupon_code' => 'SECRET10']);
        Popup::factory()->wheel()->create(['title' => 'Roue', 'pages' => ['home']]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Promo accueil', $html);
        $this->assertStringContainsString('PUBLIC10', $html);
        $this->assertStringContainsString('Roue', $html);
        $this->assertStringNotContainsString('Promo panier', $html);
        $this->assertStringNotContainsString('Désactivé', $html);
        $this->assertStringNotContainsString('Expiré', $html);
        $this->assertStringNotContainsString('Bientôt', $html);
        $this->assertStringNotContainsString('SECRET10', $html);
        $this->assertStringNotContainsString('ROUE10', $html);

        $this->get('/cart')->assertOk()->assertSee('Promo panier');
    }

    public function test_popup_events_are_counted(): void
    {
        $popup = Popup::factory()->create();

        $this->postJson(route('popups.event', $popup), ['type' => 'impression'])->assertOk();
        $this->postJson(route('popups.event', $popup), ['type' => 'click'])->assertOk();
        $this->postJson(route('popups.event', $popup), ['type' => 'hack'])->assertUnprocessable();

        $this->assertSame([1, 1], [$popup->fresh()->impressions, $popup->fresh()->clicks]);
    }

    public function test_wheel_draws_on_the_server_once_per_email_and_subscribes_the_player(): void
    {
        Coupon::factory()->create(['code' => 'ROUE10']);
        $wheel = Popup::factory()->wheel()->create();

        $this->postJson(route('wheel.spin'), ['popup_id' => $wheel->id, 'email' => 'Aya@Example.com'])
            ->assertOk()
            ->assertJsonPath('index', 0)
            ->assertJsonPath('won', true)
            ->assertJsonPath('code', 'ROUE10');

        $this->assertSame(1, WheelSpin::query()->count());
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'aya@example.com']);

        $this->postJson(route('wheel.spin'), ['popup_id' => $wheel->id, 'email' => 'aya@example.com'])->assertUnprocessable();
        $this->assertSame(1, WheelSpin::query()->count());
    }

    public function test_a_prize_whose_code_no_longer_exists_is_not_awarded(): void
    {
        $wheel = Popup::factory()->wheel()->create();

        $this->postJson(route('wheel.spin'), ['popup_id' => $wheel->id, 'email' => 'bob@example.com'])
            ->assertOk()
            ->assertJsonPath('won', false)
            ->assertJsonPath('code', null);
    }

    public function test_newsletter_popup_reveals_its_code_after_signup(): void
    {
        Coupon::factory()->create(['code' => 'MERCI10']);
        $popup = Popup::factory()->create(['kind' => 'newsletter', 'coupon_code' => 'MERCI10']);

        $this->postJson(route('newsletter.store'), ['email' => 'aya@example.com', 'popup_id' => $popup->id])
            ->assertCreated()
            ->assertJsonPath('code', 'MERCI10');

        $this->postJson(route('newsletter.store'), ['email' => 'bob@example.com'])->assertCreated()->assertJsonPath('code', null);
        $this->assertSame(2, NewsletterSubscriber::query()->count());
    }

    public function test_admin_creates_and_edits_a_wheel_with_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $base = ['name' => 'Roue été', 'kind' => 'wheel', 'title' => 'Jouez !', 'trigger' => 'delay', 'trigger_value' => 10, 'frequency' => 'once', 'devices' => 'all', 'accent' => '#237a32', 'pages' => ['home'], 'is_active' => '1'];

        $this->post(route('admin.popups.store'), $base + ['segments' => [['label' => 'Seul', 'weight' => 10]]])->assertSessionHasErrors('segments');

        $this->post(route('admin.popups.store'), $base + ['segments' => [
            ['label' => '-10 %', 'coupon_code' => 'ROUE10', 'weight' => 50, 'color' => '#237a32'],
            ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 50, 'color' => '#999999'],
        ]])->assertRedirect(route('admin.popups.index'));

        $popup = Popup::query()->firstOrFail();
        $this->assertCount(2, $popup->segments);
        $this->get(route('admin.popups.index'))->assertOk()->assertSee('Roue été');
        $this->get(route('admin.popups.edit', $popup))->assertOk();

        $this->post(route('admin.popups.store'), ['kind' => 'promo', 'coupon_code' => 'INEXISTANT'] + $base)->assertSessionHasErrors('coupon_code');

        $this->delete(route('admin.popups.destroy', $popup))->assertRedirect();
        $this->assertModelMissing($popup);
    }
}
