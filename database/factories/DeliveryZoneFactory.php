<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'fee' => fake()->randomFloat(2, 1, 10),
            'free_threshold' => null,
            'delay' => '2 à 4 jours',
            'position' => 0,
            'is_active' => true,
        ];
    }
}
