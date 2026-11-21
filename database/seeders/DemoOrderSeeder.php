<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use App\Support\Inventory;
use App\Support\OrderPlacement;
use DomainException;
use Illuminate\Database\Seeder;

/**
 * Demo customers and ~60 days of orders so the dashboard has something to show.
 * Only run in local development (see DatabaseSeeder).
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $customers = Customer::factory()->count(40)->create();
        $products = Product::query()->get();

        StockMovement::query()->whereNull('order_id')->update(['created_at' => now()->subDays(61), 'updated_at' => now()->subDays(61)]);

        for ($daysAgo = 59; $daysAgo >= 0; $daysAgo--) {
            $ordersToday = fake()->numberBetween(1, 3 + intdiv(60 - $daysAgo, 15));

            for ($n = 0; $n < $ordersToday; $n++) {
                $customer = $customers->random();
                $lines = $products->random(fake()->numberBetween(1, 4))
                    ->mapWithKeys(fn (Product $product): array => [$product->id => fake()->numberBetween(1, 3)])
                    ->all();

                try {
                    $order = OrderPlacement::place(
                        ['name' => $customer->name, 'phone' => $customer->phone, 'city' => $customer->city, 'address' => $customer->address],
                        $lines,
                        fake()->randomElement([OrderChannel::Web, OrderChannel::Web, OrderChannel::WhatsApp, OrderChannel::Phone]),
                        fake()->randomElement(PaymentMethod::cases()),
                    );
                } catch (DomainException) {
                    continue;
                }

                $placedAt = now()->subDays($daysAgo)->setTime(fake()->numberBetween(8, 21), fake()->numberBetween(0, 59));
                $status = $this->statusFor($daysAgo);

                if ($status === OrderStatus::Cancelled) {
                    OrderPlacement::changeStatus($order, OrderStatus::Cancelled);
                }

                $paid = in_array($status, [OrderStatus::Delivered, OrderStatus::Shipped], true)
                    || ($status !== OrderStatus::Cancelled && $order->payment_method !== PaymentMethod::CashOnDelivery);

                $order->forceFill([
                    'status' => $status,
                    'payment_status' => $paid ? PaymentStatus::Paid : PaymentStatus::Unpaid,
                    'placed_at' => $placedAt,
                    'paid_at' => $paid ? $placedAt->copy()->addHours(2) : null,
                    'delivered_at' => $status === OrderStatus::Delivered ? $placedAt->copy()->addDays(2) : null,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ])->save();

                $order->stockMovements()->update(['created_at' => $placedAt, 'updated_at' => $placedAt]);
            }
        }

        $lowest = Product::query()->orderBy('stock')->limit(3)->get();
        foreach ($lowest as $i => $product) {
            Inventory::setTo($product, [7, 4, 0][$i], 'Inventaire (casse constatée)');
        }
    }

    private function statusFor(int $daysAgo): OrderStatus
    {
        if (fake()->boolean(6)) {
            return OrderStatus::Cancelled;
        }

        return match (true) {
            $daysAgo > 5 => OrderStatus::Delivered,
            $daysAgo > 2 => fake()->randomElement([OrderStatus::Shipped, OrderStatus::Delivered]),
            $daysAgo > 0 => fake()->randomElement([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped]),
            default => fake()->randomElement([OrderStatus::Pending, OrderStatus::Pending, OrderStatus::Confirmed]),
        };
    }
}
