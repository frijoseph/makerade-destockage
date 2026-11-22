<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\PaymentStatus;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $product = Product::factory()->create(['name' => 'Savon Doux', 'variant' => 'Pain 100 g', 'price' => 1500, 'stock' => 20]);

        return OrderPlacement::place(
            ['name' => 'Aya Koné', 'phone' => '+2250711223344', 'city' => 'Abidjan', 'address' => 'Cocody, Riviera'],
            [$product->id => 2],
            notes: 'Appeler avant de passer',
        );
    }

    public function test_staff_can_print_the_delivery_note_with_the_amount_to_collect(): void
    {
        Setting::putMany(['print_company_details' => "RCCM CI-ABJ-2026\nCC 123456", 'print_signature_left' => 'Livreur Express']);
        $order = $this->order();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.print', [$order, 'delivery']))
            ->assertOk()
            ->assertSee('Bon de livraison')
            ->assertSee($order->number)
            ->assertSee('Savon Doux')
            ->assertSee('Cocody, Riviera')
            ->assertSee('+225 07 11 22 33 44')
            ->assertSee('Montant à encaisser')
            ->assertSee('RCCM CI-ABJ-2026')
            ->assertSee('Livreur Express')
            ->assertSee('Appeler avant de passer')
            ->assertDontSee("window.addEventListener('load'", false);
    }

    public function test_paid_orders_show_nothing_to_collect_and_auto_print_is_opt_in(): void
    {
        $order = $this->order();
        OrderPlacement::changePaymentStatus($order, PaymentStatus::Paid);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.orders.print', [$order, 'delivery', 'auto' => 1]))->assertOk()
            ->assertSee('rien à encaisser')
            ->assertSee("window.addEventListener('load'", false);

        Setting::putMany(['print_auto_open' => false]);
        $this->get(route('admin.orders.print', [$order, 'delivery', 'auto' => 1]))->assertDontSee("window.addEventListener('load'", false);
    }

    public function test_delivery_note_can_hide_prices_and_ticket_width_is_configurable(): void
    {
        Setting::putMany(['print_delivery_show_prices' => false, 'print_ticket_width' => '58mm']);
        $order = $this->order();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.orders.print', [$order, 'delivery']))->assertOk()->assertDontSee('Sous-total');
        $this->get(route('admin.orders.print', [$order, 'ticket']))->assertOk()->assertSee('58mm auto', false)->assertSee('Montant à encaisser');
        $this->get(route('admin.orders.print', [$order, 'receipt']))->assertOk()->assertSee('Sous-total');
        $this->get('/admin/orders/'.$order->id.'/print/invoice')->assertNotFound();
    }

    public function test_order_preview_modal_and_buttons_are_available_to_staff_only(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.orders.preview', $order))->assertOk()->assertSee('Savon Doux')->assertSee('Bon de livraison');
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('data-order-preview', false);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Ticket');

        auth()->logout();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.orders.print', [$order, 'receipt']))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.preview', $order))->assertRedirect(route('admin.login'));
    }

    public function test_customers_print_their_receipt_through_a_signed_link_only(): void
    {
        $order = $this->order();
        $signed = URL::temporarySignedRoute('checkout.receipt', now()->addHour(), ['order' => $order->number]);

        $this->get($signed)->assertOk()->assertSee('Reçu')->assertSee($order->number)->assertSee('Savon Doux')->assertDontSee('Bon de livraison');
        $this->get(route('checkout.receipt', ['order' => $order->number]))->assertForbidden();

        $confirmation = URL::temporarySignedRoute('checkout.show', now()->addHour(), ['order' => $order->number]);
        $this->get($confirmation)->assertOk()->assertSee('Imprimer mon reçu');
    }

    public function test_print_settings_are_editable_from_the_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.settings.edit', 'documents'))->assertOk()->assertSee('Titre du bon de livraison');
        $this->put(route('admin.settings.update', 'documents'), ['settings' => [
            'print_company_name' => 'Makerade SARL', 'print_delivery_title' => 'BL', 'print_receipt_title' => 'Reçu de caisse',
            'print_delivery_note' => 'Note', 'print_signature_left' => 'A', 'print_signature_right' => 'B', 'print_footer' => 'Merci', 'print_ticket_width' => '80mm', 'print_company_details' => '',
        ]])->assertSessionHasNoErrors();

        $this->assertSame('Makerade SARL', Setting::get('print_company_name'));
    }
}
