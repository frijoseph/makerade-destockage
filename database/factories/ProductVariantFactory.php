<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $size = fake()->unique()->randomElement(['250 ml', '500 ml', '750 ml', '1 L', '2 L', '5 L', '10 L', '20 L']);

        return [
            'product_id' => Product::factory(),
            'name' => $size,
            'option1' => $size,
            'sku' => null,
            'price' => null,
            'old_price' => null,
            'stock' => 10,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
