<?php

namespace Database\Factories;

use App\Models\Popup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Popup>
 */
class PopupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake('fr_FR')->words(2, true),
            'kind' => 'promo',
            'title' => fake('fr_FR')->sentence(4),
            'body' => fake('fr_FR')->sentence(),
            'trigger' => 'delay',
            'trigger_value' => 5,
            'frequency' => 'always',
            'pages' => ['all'],
            'devices' => 'all',
            'accent' => '#237a32',
            'is_active' => true,
        ];
    }

    public function wheel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => 'wheel',
            'segments' => [
                ['label' => '-10 %', 'coupon_code' => 'ROUE10', 'weight' => 100, 'color' => '#237a32'],
                ['label' => 'Perdu', 'coupon_code' => '', 'weight' => 0, 'color' => '#999999'],
            ],
        ]);
    }
}
