<?php

namespace Tests\Feature;

use App\Models\AiMessage;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChatBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
        Setting::putMany(['ai_enabled' => true, 'shop_name' => 'Boutique Test']);
        Http::fake();
    }

    private function ask(string $message, ?string $conversation = null): TestResponse
    {
        return $this->postJson(route('assistant.message'), ['message' => $message, 'conversation' => $conversation]);
    }

    public function test_the_bot_is_the_default_and_never_calls_an_api(): void
    {
        $this->ask('Bonjour')->assertOk()->assertJsonPath('fallback', false)->assertSee('Boutique Test');
        $this->ask('Merci beaucoup')->assertOk()->assertSee('Avec plaisir');

        Http::assertNothingSent();
    }

    public function test_shop_rules_take_priority_and_are_editable(): void
    {
        Setting::putMany(['bot_rules' => [['keywords' => 'horaires, ouverture', 'reply' => 'Ouvert de 8h à 18h chez {shop_name}.']]]);

        $this->ask('Quels sont vos horaires ?')->assertOk()->assertJsonPath('reply', 'Ouvert de 8h à 18h chez Boutique Test.');
    }

    public function test_it_answers_delivery_payment_returns_and_contact_from_the_settings(): void
    {
        Setting::putMany(['shipping_fee' => 1500, 'return_days' => 14, 'contact_phone' => '+225 07 00 00 00 00']);

        $this->ask('Quels sont vos délais de livraison ?')->assertOk()->assertSee('livraison');
        $this->ask('Comment puis-je payer ?')->assertOk()->assertSee('paiement');
        $this->ask('Puis-je retourner un article ?')->assertOk()->assertSee('14 jours');
        $this->ask('Comment vous contacter ?')->assertOk()->assertSee('07 00 00 00 00');
    }

    public function test_it_finds_products_and_returns_cards(): void
    {
        Product::factory()->create(['name' => 'Lessive Aloe Vera', 'stock' => 12, 'price' => 3500]);

        $response = $this->ask('Avez-vous de la lessive ?')->assertOk();

        $this->assertStringContainsString('Lessive Aloe Vera', $response->json('reply'));
        $this->assertSame('Lessive Aloe Vera', $response->json('products.0.name'));
    }

    public function test_unknown_questions_get_the_configured_reply_and_are_flagged(): void
    {
        Setting::putMany(['bot_unknown_reply' => 'Je ne sais pas encore.']);

        $this->ask('zxqv wkrt')->assertOk()->assertJsonPath('reply', 'Je ne sais pas encore.');
        $this->assertTrue(AiMessage::query()->where('role', 'assistant')->firstOrFail()->meta['fallback']);
    }

    public function test_orders_are_only_disclosed_with_number_and_matching_phone(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = OrderPlacement::place(['name' => 'Aya', 'phone' => '+2250711223344', 'city' => 'Daloa'], [$product->id => 1]);

        $this->assertStringContainsString('numéro de commande', $this->ask('Où en est ma commande ?')->assertOk()->json('reply'));

        $wrong = $this->ask("Commande {$order->number} téléphone 07 99 99 99 99")->assertOk();
        $this->assertStringNotContainsString('Daloa', $wrong->json('reply'));

        $right = $this->ask("Commande {$order->number}, téléphone +225 07 11 22 33 44", $wrong->json('conversation'))->assertOk();
        $this->assertStringContainsString('Daloa', $right->json('reply'));
    }

    public function test_a_logged_in_customer_sees_only_their_own_orders(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();
        $mine = OrderPlacement::place(['name' => 'Moi', 'phone' => '+2250700000001', 'city' => 'Abidjan'], [$product->id => 1], account: $user);
        $theirs = OrderPlacement::place(['name' => 'Autre', 'phone' => '+2250700000002', 'city' => 'Bouaké'], [$product->id => 1]);

        $reply = $this->actingAs($user)->ask('Où en est ma commande ?')->assertOk()->json('reply');
        $this->assertStringContainsString($mine->number, $reply);
        $this->assertStringNotContainsString($theirs->number, $reply);
    }

    public function test_choosing_the_model_without_a_key_still_answers_with_the_bot(): void
    {
        Setting::putMany(['ai_mode' => 'llm', 'ai_api_key' => '']);

        $this->ask('Bonjour')->assertOk()->assertJsonPath('fallback', false);
        Http::assertNothingSent();
    }
}
