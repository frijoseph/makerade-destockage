<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\OrderChannel;
use App\OrderStatus;
use App\PaymentMethod;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bare order without items; use OrderPlacement to create orders that move stock.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 80);

        return [
            'number' => 'MK-'.now()->format('Y').'-'.fake()->unique()->numerify('9####'),
            'customer_id' => Customer::factory(),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'channel' => OrderChannel::Web,
            'subtotal' => $subtotal,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'placed_at' => now(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Delivered,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'delivered_at' => now(),
        ]);
    }
}
