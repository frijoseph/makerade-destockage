<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));
        $price = fake()->randomFloat(2, 1, 20);

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'slug' => Str::slug($name),
            'sku' => 'MK-'.fake()->unique()->numerify('#####'),
            'name' => $name,
            'variant' => fake()->numberBetween(100, 1000).' ml',
            'price' => $price,
            'old_price' => null,
            'cost_price' => round($price * 0.6, 2),
            'stock' => fake()->numberBetween(20, 100),
            'low_stock_threshold' => 10,
            'kind' => fake()->randomElement(Product::KINDS),
            'c1' => fake()->hexColor(),
            'c2' => fake()->hexColor(),
            'is_active' => true,
        ];
    }

    public function discounted(int $percent = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'old_price' => round($attributes['price'] / (1 - $percent / 100), 2),
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock' => 3, 'low_stock_threshold' => 10]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
