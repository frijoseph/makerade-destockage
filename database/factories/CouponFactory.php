<?php

namespace Database\Factories;

use App\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PROMO##??')),
            'label' => fake('fr_FR')->words(3, true),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_subtotal' => 0,
            'is_active' => true,
        ];
    }

    public function fixed(float $amount): static
    {
        return $this->state(fn (array $attributes): array => ['type' => CouponType::Fixed, 'value' => $amount]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => CouponType::FreeShipping, 'value' => 0]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes): array => ['auto_apply' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => ['ends_at' => now()->subDay()]);
    }
}
