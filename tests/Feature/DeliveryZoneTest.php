<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrderPlacement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_zones_from_the_settings_table(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.settings.edit', 'delivery'))->assertOk()->assertSee('Zones de livraison');

        $this->postJson(route('admin.zones.store'), ['name' => 'Abidjan', 'fee' => 3, 'free_threshold' => 40, 'delay' => '24 h', 'is_active' => true])->assertCreated();
        $zone = DeliveryZone::query()->firstOrFail();

        // The modal sends empty optional fields as null: the position must still get a default.
        $this->postJson(route('admin.zones.store'), ['name' => 'Bouaké', 'fee' => null, 'free_threshold' => null, 'delay' => null, 'position' => null, 'is_active' => true])->assertCreated();
        $this->assertNotNull(DeliveryZone::query()->where('name', 'Bouaké')->value('position'));

        $this->postJson(route('admin.zones.store'), ['name' => 'Abidjan'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson(route('admin.zones.store'), ['name' => ''])->assertUnprocessable();
        $this->postJson(route('admin.zones.store'), ['name' => 'Man', 'fee' => -2])->assertJsonValidationErrors('fee');

        $this->putJson(route('admin.zones.update', $zone), ['name' => 'Abidjan', 'fee' => null, 'delay' => '48 h', 'is_active' => true])->assertOk();
        $this->assertNull($zone->fresh()->fee);
        $this->get(route('admin.settings.edit', 'delivery'))->assertOk()->assertSee('48 h');

        $this->deleteJson(route('admin.zones.destroy', $zone))->assertOk();
        $this->assertModelMissing($zone);
    }

    public function test_managers_cannot_edit_zones(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->postJson(route('admin.zones.store'), ['name' => 'Abidjan'])
            ->assertForbidden();
    }

    public function test_shipping_fee_follows_the_city_zone_and_falls_back_to_defaults(): void
    {
        Setting::putMany(['shipping_fee' => 5, 'free_shipping_threshold' => 100]);
        DeliveryZone::factory()->create(['name' => 'Abidjan', 'fee' => 2, 'free_threshold' => 30]);
        DeliveryZone::factory()->create(['name' => 'Man', 'fee' => null, 'free_threshold' => null]);
        DeliveryZone::factory()->create(['name' => 'Fermée', 'fee' => 1, 'is_active' => false]);

        $this->assertSame(2.0, OrderPlacement::shippingFeeFor(20, ' abidjan '));
        $this->assertSame(0.0, OrderPlacement::shippingFeeFor(35, 'Abidjan'));
        $this->assertSame(5.0, OrderPlacement::shippingFeeFor(35, 'Man'));
        $this->assertSame(5.0, OrderPlacement::shippingFeeFor(20, 'Fermée'));
        $this->assertSame(5.0, OrderPlacement::shippingFeeFor(20));
        $this->assertSame(0.0, OrderPlacement::shippingFeeFor(0, 'Abidjan'));
    }

    public function test_checkout_charges_the_zone_fee_and_can_refuse_other_cities(): void
    {
        Product::factory()->create(['slug' => 'savon-test', 'price' => 10, 'stock' => 20]);
        DeliveryZone::factory()->create(['name' => 'Abidjan', 'fee' => 2.5, 'free_threshold' => null]);
        $payload = ['name' => 'Aya', 'phone' => '0700000001', 'city' => 'Abidjan', 'address' => 'Cocody', 'payment_method' => 'wave', 'items' => ['savon-test' => 1]];

        $this->postJson(route('checkout.store'), $payload)->assertCreated();
        $this->assertEquals(12.5, Order::query()->firstOrFail()->total);

        $this->postJson(route('checkout.store'), ['city' => 'Tombouctou'] + $payload)->assertCreated();

        Setting::putMany(['restrict_to_zones' => true]);
        $this->postJson(route('checkout.store'), ['city' => 'Tombouctou'] + $payload)->assertUnprocessable()->assertJsonValidationErrors('city');
        $this->postJson(route('checkout.store'), $payload)->assertCreated();
    }
}
