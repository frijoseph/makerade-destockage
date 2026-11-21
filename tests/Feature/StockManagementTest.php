<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\StockMovementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_loss_and_inventory_count_are_traced(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->actingAs($admin)->post(route('admin.stock.store'), ['product_id' => $product->id, 'type' => 'restock', 'quantity' => 15, 'reason' => 'Arrivage'])
            ->assertSessionHasNoErrors();
        $this->assertSame(25, $product->fresh()->stock);

        $this->post(route('admin.stock.store'), ['product_id' => $product->id, 'type' => 'loss', 'quantity' => 3]);
        $this->assertSame(22, $product->fresh()->stock);

        $this->post(route('admin.stock.store'), ['product_id' => $product->id, 'type' => 'adjustment', 'quantity' => 20]);
        $this->assertSame(20, $product->fresh()->stock);

        $this->assertSame([15, -3, -2], StockMovement::query()->orderBy('id')->pluck('quantity')->all());
        $this->assertSame([25, 22, 20], StockMovement::query()->orderBy('id')->pluck('stock_after')->all());
        $this->assertSame($admin->id, StockMovement::query()->value('user_id'));
    }

    public function test_loss_cannot_make_stock_negative(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.stock.store'), ['product_id' => $product->id, 'type' => 'loss', 'quantity' => 5])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_sales_cannot_be_entered_by_hand(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.stock.store'), ['product_id' => $product->id, 'type' => StockMovementType::Sale->value, 'quantity' => 1])
            ->assertSessionHasErrors('type');
    }
}
