<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word());

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'color' => fake()->hexColor(),
            'style' => fake()->randomElement(Brand::STYLES),
            'is_active' => true,
        ];
    }
}
